<?php

namespace App\Services;

use App\Services\Fetcher\PageFetcherInterface;
use Illuminate\Support\Facades\Log;

/**
 * Fetches sitemap.xml (or a sitemap index referencing multiple sitemaps)
 * and returns the flat list of page URLs it declares. This finds pages
 * that link-following alone would miss — a common real-world gap that
 * pure link-crawlers have.
 */
class SitemapParser
{
    public function __construct(private PageFetcherInterface $fetcher)
    {
    }

    /**
     * @return string[] absolute page URLs found in the sitemap(s)
     */
    public function fetchUrls(string $sitemapUrl, int $depth = 0): array
    {
        if ($depth > 3) {
            return []; // guard against a pathological sitemap-index loop
        }

        $result = $this->fetcher->fetch($sitemapUrl, 15);

        if (! $result->success || ! $result->body) {
            return [];
        }

        $xml = @simplexml_load_string($result->body, 'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR);
        if ($xml === false) {
            Log::channel('crawler')->info('Sitemap not valid XML, skipping', ['url' => $sitemapUrl]);

            return [];
        }

        $urls = [];

        // Sitemap index: <sitemapindex><sitemap><loc>...</loc></sitemap>...
        if (isset($xml->sitemap)) {
            foreach ($xml->sitemap as $entry) {
                $loc = trim((string) ($entry->loc ?? ''));
                if ($loc !== '') {
                    $urls = array_merge($urls, $this->fetchUrls($loc, $depth + 1));
                }
            }

            return $urls;
        }

        // Regular urlset: <urlset><url><loc>...</loc></url>...
        if (isset($xml->url)) {
            foreach ($xml->url as $entry) {
                $loc = trim((string) ($entry->loc ?? ''));
                if ($loc !== '') {
                    $urls[] = $loc;
                }
            }
        }

        // Cap defensively — a huge sitemap shouldn't blow past a project's max_pages anyway,
        // that's enforced by the caller, but this avoids holding an absurd array in memory.
        return array_slice($urls, 0, 20000);
    }

    /**
     * Try the project's configured sitemap_url; if blank, guess the
     * conventional /sitemap.xml location.
     */
    public function resolveSitemapUrl(?string $configured, string $baseUrl): string
    {
        if ($configured) {
            return $configured;
        }

        return rtrim($baseUrl, '/') . '/sitemap.xml';
    }
}
