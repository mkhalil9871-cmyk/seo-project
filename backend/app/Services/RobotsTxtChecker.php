<?php

namespace App\Services;

use App\Services\Fetcher\PageFetcherInterface;
use Illuminate\Support\Facades\Cache;

class RobotsTxtChecker
{
    /** @var array<string,array{disallow: string[], allow: string[], sitemaps: string[]}> */
    private array $cache = [];

    public function __construct(private PageFetcherInterface $fetcher)
    {
    }

    public function isAllowed(string $url, string $userAgent = '*'): bool
    {
        $rules = $this->getRules($url);
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        $longestAllow = $this->longestMatch($path, $rules['allow']);
        $longestDisallow = $this->longestMatch($path, $rules['disallow']);

        // Standard robots.txt precedence: longest matching rule wins; Allow beats a tie.
        if ($longestDisallow === 0) {
            return true;
        }

        return $longestAllow >= $longestDisallow;
    }

    /** @return string[] */
    public function getSitemaps(string $anyUrlOnSite): array
    {
        return $this->getRules($anyUrlOnSite)['sitemaps'];
    }

    private function getRules(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $key = "{$scheme}://{$host}";

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // Cache robots.txt across audit batches too — no point re-fetching every cron tick.
        $cacheKey = 'robots:' . md5($key);
        $rules = Cache::remember($cacheKey, now()->addHours(6), function () use ($key) {
            return $this->fetchAndParse($key . '/robots.txt');
        });

        $this->cache[$key] = $rules;

        return $rules;
    }

    private function fetchAndParse(string $robotsUrl): array
    {
        $result = $this->fetcher->fetch($robotsUrl, 10);

        $rules = ['disallow' => [], 'allow' => [], 'sitemaps' => []];

        if (! $result->success || ! $result->body) {
            // No robots.txt = everything allowed by default.
            return $rules;
        }

        $applies = false;
        foreach (preg_split('/\r\n|\r|\n/', $result->body) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if ($line === '') {
                continue;
            }

            if (! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                $applies = ($value === '*' || $value === '');
                continue;
            }

            if ($field === 'sitemap') {
                $rules['sitemaps'][] = $value;
                continue;
            }

            if (! $applies) {
                continue;
            }

            if ($field === 'disallow' && $value !== '') {
                $rules['disallow'][] = $value;
            } elseif ($field === 'allow' && $value !== '') {
                $rules['allow'][] = $value;
            }
        }

        return $rules;
    }

    private function longestMatch(string $path, array $patterns): int
    {
        $best = 0;
        foreach ($patterns as $pattern) {
            $regex = '#^' . str_replace(['\*', '\$'], ['.*', '$'], preg_quote($pattern, '#')) . '#';
            if (@preg_match($regex, $path) && preg_match($regex, $path)) {
                $best = max($best, strlen($pattern));
            }
        }

        return $best;
    }
}
