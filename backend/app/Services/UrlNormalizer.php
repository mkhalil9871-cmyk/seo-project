<?php

namespace App\Services;

class UrlNormalizer
{
    /**
     * Resolve a possibly-relative href against the page it was found on,
     * then normalize it into a canonical, comparable form.
     */
    public static function resolve(string $href, string $baseUrl): ?string
    {
        $href = trim($href);

        // Real browsers treat backslashes as forward slashes for "special" schemes
        // (http/https/ws/wss/ftp/file) per the WHATWG URL spec — so a link written
        // as href="\/page.html" still works when a user clicks it. PHP's parse_url()
        // does NOT do this normalization, so without this line the crawler would
        // build a literal ".../\/page.html" URL, fail to fetch it, and wrongly
        // report it as a broken link even though it works fine for a real visitor.
        $href = str_replace('\\', '/', $href);

        if ($href === '' || str_starts_with($href, '#')
            || str_starts_with($href, 'javascript:')
            || str_starts_with($href, 'mailto:')
            || str_starts_with($href, 'tel:')) {
            return null;
        }

        // Already absolute
        if (preg_match('#^https?://#i', $href)) {
            return self::normalize($href);
        }

        $base = parse_url($baseUrl);
        if (! $base || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }

        $scheme = $base['scheme'];
        $host = $base['host'];
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (str_starts_with($href, '//')) {
            $absolute = $scheme . ':' . $href;
        } elseif (str_starts_with($href, '/')) {
            $absolute = "{$scheme}://{$host}{$port}{$href}";
        } else {
            // Relative to current path.
            //
            // NOTE: deliberately NOT using PHP's dirname() here. dirname() is OS-aware —
            // on Windows, dirname('/index.html') returns '\' (a literal backslash) instead
            // of '/', because Windows treats a bare root differently. That single character
            // was leaking straight into crawled URLs (".../\/index.html"), producing
            // false-positive "broken link" issues. strrpos/substr below do the same job
            // using plain string search, so the result is identical on every OS.
            $basePath = $base['path'] ?? '/';
            $lastSlash = strrpos($basePath, '/');
            $dir = $lastSlash === false ? '/' : substr($basePath, 0, $lastSlash + 1);
            $absolute = "{$scheme}://{$host}{$port}" . self::collapseDots($dir . $href);
        }

        return self::normalize($absolute);
    }

    public static function normalize(string $url): string
    {
        $parts = parse_url($url);
        if (! $parts) {
            return $url;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? null;

        // Drop default ports
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        $path = $parts['path'] ?? '/';
        $path = self::collapseDots($path);
        // Remove trailing slash except for root
        if (strlen($path) > 1 && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        if ($path === '') {
            $path = '/';
        }

        $query = '';
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);
            // Strip common tracking params that would otherwise create false "duplicate" pages
            foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'ref'] as $tracking) {
                unset($params[$tracking]);
            }
            if (! empty($params)) {
                ksort($params);
                $query = '?' . http_build_query($params);
            }
        }

        // Fragment (#...) is intentionally dropped — it never changes the fetched resource.
        $portStr = $port ? ":{$port}" : '';

        return "{$scheme}://{$host}{$portStr}{$path}{$query}";
    }

    public static function hash(string $normalizedUrl): string
    {
        return hash('sha256', $normalizedUrl);
    }

    public static function isSameHost(string $url, string $baseUrl): bool
    {
        $a = parse_url($url, PHP_URL_HOST);
        $b = parse_url($baseUrl, PHP_URL_HOST);

        return $a && $b && strtolower($a) === strtolower($b);
    }

    private static function collapseDots(string $path): string
    {
        $segments = explode('/', $path);
        $result = [];
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }
            if ($segment === '..') {
                array_pop($result);
                continue;
            }
            $result[] = $segment;
        }

        return '/' . implode('/', $result) . (str_ends_with($path, '/') && $path !== '/' ? '/' : '');
    }
}