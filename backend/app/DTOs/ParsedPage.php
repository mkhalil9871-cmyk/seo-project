<?php

namespace App\DTOs;

final class ParsedPage
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $metaDescription = null,
        public readonly ?string $metaRobots = null,
        public readonly ?string $canonicalUrl = null,
        public readonly ?string $charset = null,
        public readonly ?string $lang = null,
        public readonly bool $hasViewport = false,
        public readonly array $headings = [],           // ['h1' => [...], 'h2' => [...]]
        public readonly int $wordCount = 0,
        public readonly array $links = [],               // [['url'=>, 'anchor'=>, 'nofollow'=>bool], ...]
        public readonly int $imageCount = 0,
        public readonly int $imagesMissingAlt = 0,
        public readonly array $jsonLd = [],
        public readonly bool $hasSchema = false,
        public readonly array $resourceUrls = [],         // absolute src URLs of img/script/link[stylesheet], for mixed-content checks
        public readonly array $hreflangs = [],             // [['lang'=>'en-us','url'=>'...'], ...]
        public readonly ?string $relNext = null,
        public readonly ?string $relPrev = null,
        public readonly ?string $bodyTextHash = null,      // sha256 of normalized visible text, for duplicate-content detection
    ) {
    }

    public function isIndexable(): bool
    {
        if (! $this->metaRobots) {
            return true;
        }

        return ! str_contains(strtolower($this->metaRobots), 'noindex');
    }
}
