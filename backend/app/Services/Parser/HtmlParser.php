<?php

namespace App\Services\Parser;

use App\DTOs\ParsedPage;
use App\Services\UrlNormalizer;
use DOMDocument;
use DOMXPath;

/**
 * Uses PHP's built-in DOMDocument/libxml — ext-dom is available on essentially
 * every shared PHP host, unlike Playwright/Puppeteer/Chromium.
 */
class HtmlParser
{
    public function parse(string $html, string $pageUrl): ParsedPage
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // suppress warnings from malformed real-world HTML
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $title = $this->text($xpath, '//title[1]');
        $metaDescription = $this->metaContent($xpath, 'description');
        $metaRobots = $this->metaContent($xpath, 'robots');
        $canonical = $this->linkHref($xpath, 'canonical');
        $charset = $this->extractCharset($xpath);
        $lang = $this->attr($xpath, '//html[1]', 'lang');
        $hasViewport = $this->metaContent($xpath, 'viewport') !== null;

        $headings = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $nodes = $xpath->query("//{$tag}");
            $texts = [];
            foreach ($nodes as $node) {
                $t = trim($node->textContent);
                if ($t !== '') {
                    $texts[] = $t;
                }
            }
            if ($texts) {
                $headings[$tag] = $texts;
            }
        }

        $bodyText = $this->text($xpath, '//body[1]') ?? '';
        $cleanBodyText = trim(preg_replace('/\s+/', ' ', strip_tags($bodyText)));
        $wordCount = str_word_count($cleanBodyText);
        // Hash normalized text so near-duplicate detection isn't thrown off by whitespace differences.
        $bodyTextHash = $cleanBodyText !== '' ? hash('sha256', mb_strtolower($cleanBodyText)) : null;

        $links = [];
        foreach ($xpath->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            $resolved = UrlNormalizer::resolve($href, $pageUrl);
            if (! $resolved) {
                continue;
            }
            $rel = strtolower($a->getAttribute('rel'));
            $links[] = [
                'url' => $resolved,
                'anchor' => trim($a->textContent),
                'nofollow' => str_contains($rel, 'nofollow'),
            ];
        }

        $images = $xpath->query('//img');
        $imageCount = $images->length;
        $missingAlt = 0;
        $resourceUrls = [];
        foreach ($images as $img) {
            $alt = $img->getAttribute('alt');
            if (trim($alt) === '') {
                $missingAlt++;
            }
            $src = $img->getAttribute('src');
            if ($src) {
                $resolved = UrlNormalizer::resolve($src, $pageUrl);
                if ($resolved) {
                    $resourceUrls[] = $resolved;
                }
            }
        }

        // Scripts and stylesheets — needed for mixed-content detection (https page loading http resource).
        foreach ($xpath->query('//script[@src]') as $script) {
            $resolved = UrlNormalizer::resolve($script->getAttribute('src'), $pageUrl);
            if ($resolved) {
                $resourceUrls[] = $resolved;
            }
        }
        foreach ($xpath->query('//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="stylesheet"]') as $link) {
            $resolved = UrlNormalizer::resolve($link->getAttribute('href'), $pageUrl);
            if ($resolved) {
                $resourceUrls[] = $resolved;
            }
        }

        $jsonLd = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $decoded = json_decode(trim($script->textContent), true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                $jsonLd[] = $decoded;
            }
        }
        $hasMicrodata = $xpath->query('//*[@itemscope]')->length > 0;

        // hreflang tags — <link rel="alternate" hreflang="..." href="...">
        $hreflangs = [];
        foreach ($xpath->query('//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="alternate"][@hreflang]') as $node) {
            $href = $node->getAttribute('href');
            $resolved = $href ? UrlNormalizer::resolve($href, $pageUrl) : null;
            $hreflangs[] = ['lang' => $node->getAttribute('hreflang'), 'url' => $resolved];
        }

        // rel=next / rel=prev pagination hints
        $relNext = $this->linkHref($xpath, 'next');
        $relPrev = $this->linkHref($xpath, 'prev');

        return new ParsedPage(
            title: $title,
            metaDescription: $metaDescription,
            metaRobots: $metaRobots,
            canonicalUrl: $canonical ? UrlNormalizer::resolve($canonical, $pageUrl) : null,
            charset: $charset,
            lang: $lang,
            hasViewport: $hasViewport,
            headings: $headings,
            wordCount: $wordCount,
            links: $links,
            imageCount: $imageCount,
            imagesMissingAlt: $missingAlt,
            jsonLd: $jsonLd,
            hasSchema: ! empty($jsonLd) || $hasMicrodata,
            resourceUrls: array_values(array_unique($resourceUrls)),
            hreflangs: $hreflangs,
            relNext: $relNext ? UrlNormalizer::resolve($relNext, $pageUrl) : null,
            relPrev: $relPrev ? UrlNormalizer::resolve($relPrev, $pageUrl) : null,
            bodyTextHash: $bodyTextHash,
        );
    }

    private function text(DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);

        return $node ? trim($node->textContent) : null;
    }

    private function attr(DOMXPath $xpath, string $query, string $attr): ?string
    {
        $node = $xpath->query($query)->item(0);

        return $node?->attributes?->getNamedItem($attr)?->nodeValue;
    }

    private function metaContent(DOMXPath $xpath, string $name): ?string
    {
        $node = $xpath->query("//meta[translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$name}']")->item(0);
        $content = $node?->attributes?->getNamedItem('content')?->nodeValue;

        return $content !== null ? trim($content) : null;
    }

    private function linkHref(DOMXPath $xpath, string $rel): ?string
    {
        $node = $xpath->query("//link[translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$rel}']")->item(0);

        return $node?->attributes?->getNamedItem('href')?->nodeValue;
    }

    private function extractCharset(DOMXPath $xpath): ?string
    {
        $node = $xpath->query('//meta[@charset]')->item(0);
        if ($node) {
            return $node->attributes->getNamedItem('charset')->nodeValue;
        }

        return $this->metaContent($xpath, 'content-type');
    }
}
