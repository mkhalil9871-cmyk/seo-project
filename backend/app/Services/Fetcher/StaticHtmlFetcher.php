<?php

namespace App\Services\Fetcher;

use App\DTOs\FetchResult;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Fetches raw HTML over HTTP(S) only — no JS execution.
 * Sufficient for the ~90% of technical SEO signals that live in server-rendered HTML.
 */
class StaticHtmlFetcher implements PageFetcherInterface
{
    public function fetch(string $url, int $timeoutSeconds = 15): FetchResult
    {
        $results = $this->fetchMany([$url], 1, $timeoutSeconds);

        return $results[$url] ?? FetchResult::failure('Unknown fetch error');
    }

    /**
     * Fetches all given URLs concurrently over a shared connection pool.
     *
     * This is the main crawl-speed lever: a sequential batch of 10 pages at ~400ms
     * network latency each takes ~4s; the same 10 pages with concurrency=5 take
     * roughly ~2 round trips (~800ms-1s), since requests overlap while waiting on
     * the remote server rather than waiting on each other. Still pure PHP + curl
     * under the hood (Guzzle's pool just multiplexes curl handles), so it needs
     * nothing beyond what already runs fine on shared hosting — no extra processes,
     * no extra memory beyond holding `concurrency` responses at once instead of 1.
     *
     * @param string[] $urls
     * @return array<string, FetchResult> keyed by the exact URL string passed in
     */
    public function fetchMany(array $urls, int $concurrency = 5, int $timeoutSeconds = 15): array
    {
        $results = [];
        $maxBytes = config('crawler.max_page_bytes', 3_000_000);
        $userAgent = config('crawler.user_agent', 'SEOCrawlerBot/1.0 (+https://example.com/bot)');

        $client = new Client([
            'timeout' => $timeoutSeconds,
            'connect_timeout' => 5,
            'allow_redirects' => ['max' => 5, 'track_redirects' => true],
            'headers' => [
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml',
            ],
            'http_errors' => false,
        ]);

        // Filter out anything that fails the SSRF guard up front — never even queue those requests.
        $safeUrls = [];
        foreach ($urls as $url) {
            if (UrlSafetyGuard::isSafeToFetch($url)) {
                $safeUrls[] = $url;
            } else {
                $results[$url] = FetchResult::failure('Blocked by URL safety guard (private/loopback/metadata IP or invalid scheme)');
            }
        }

        if (empty($safeUrls)) {
            return $results;
        }

        $timings = [];
        $requests = function () use ($safeUrls, $client, &$timings) {
            foreach ($safeUrls as $url) {
                $timings[$url] = microtime(true);
                yield $url => new \GuzzleHttp\Psr7\Request('GET', $url);
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests(), [
            'concurrency' => max(1, $concurrency),
            'fulfilled' => function ($response, $url) use (&$results, $maxBytes, &$timings) {
                $results[$url] = $this->buildResult($response, $url, $timings[$url] ?? microtime(true), $maxBytes);
            },
            'rejected' => function ($reason, $url) use (&$results, &$timings) {
                $elapsedMs = (int) round((microtime(true) - ($timings[$url] ?? microtime(true))) * 1000);
                $message = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                Log::channel('crawler')->warning('Fetch failed', ['url' => $url, 'error' => $message]);
                $results[$url] = FetchResult::failure($message, $elapsedMs);
            },
        ]);

        try {
            $pool->promise()->wait();
        } catch (\Throwable $e) {
            // Guzzle's promise pool can occasionally fail to resolve cleanly (seen on Windows
            // when a connection in the batch hangs/resets oddly) — this must NEVER take down
            // the whole batch/audit. Whichever URLs already got a 'fulfilled'/'rejected'
            // callback keep their real result above; anything still missing just gets marked
            // failed here so the crawl can continue on the next cron tick.
            Log::channel('crawler')->error('Fetch pool failed to resolve', ['error' => $e->getMessage(), 'urls' => $safeUrls]);
            foreach ($safeUrls as $url) {
                $results[$url] ??= FetchResult::failure('Batch fetch error: ' . $e->getMessage());
            }
        }

        return $results;
    }

    private function buildResult($response, string $requestedUrl, float $startedAt, int $maxBytes): FetchResult
    {
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaderLine('Content-Type');
        $xRobotsTag = $response->getHeaderLine('X-Robots-Tag') ?: null;

        // Don't try to parse non-HTML payloads (PDFs, images, etc.) as pages.
        if ($contentType && ! str_contains($contentType, 'html')) {
            return new FetchResult(
                success: true,
                statusCode: $statusCode,
                body: null,
                finalUrl: $requestedUrl,
                redirectTo: null,
                responseTimeMs: $elapsedMs,
                contentType: $contentType,
                errorMessage: 'Non-HTML content-type, skipped parsing',
                xRobotsTag: $xRobotsTag,
            );
        }

        $redirectHistory = $response->getHeader('X-Guzzle-Redirect-History');
        $finalUrl = ! empty($redirectHistory) ? end($redirectHistory) : $requestedUrl;

        $body = (string) $response->getBody();

        // Hard cap on body size read into memory — protects shared-hosting RAM limits.
        if (strlen($body) > $maxBytes) {
            $body = substr($body, 0, $maxBytes);
        }

        return new FetchResult(
            success: true,
            statusCode: $statusCode,
            body: $body,
            finalUrl: $finalUrl,
            redirectTo: ($statusCode >= 300 && $statusCode < 400) ? $response->getHeaderLine('Location') : null,
            responseTimeMs: $elapsedMs,
            contentType: $contentType,
            xRobotsTag: $xRobotsTag,
        );
    }
}