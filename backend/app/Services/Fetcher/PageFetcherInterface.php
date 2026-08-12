<?php

namespace App\Services\Fetcher;

use App\DTOs\FetchResult;

/**
 * Contract for fetching a URL's raw response.
 *
 * Today: StaticHtmlFetcher (curl/Guzzle, no JS execution — works on any shared host).
 * Later: swap in a HeadlessBrowserFetcher (Playwright/Puppeteer via a VPS microservice)
 * by binding a different implementation in AppServiceProvider — nothing else in the
 * crawler, parser, or audit code needs to change.
 */
interface PageFetcherInterface
{
    public function fetch(string $url, int $timeoutSeconds = 15): FetchResult;

    /**
     * Fetch many URLs concurrently and return results keyed by the original URL string.
     * StaticHtmlFetcher does this with a Guzzle connection pool (cheap: still just PHP + curl,
     * works on any shared host). A future HeadlessBrowserFetcher should fire concurrent requests
     * at its rendering microservice here too — the concurrency win applies either way, since in
     * both cases the bottleneck is waiting on network I/O, not local CPU.
     *
     * @param string[] $urls
     * @return array<string, FetchResult>
     */
    public function fetchMany(array $urls, int $concurrency = 5, int $timeoutSeconds = 15): array;
}
