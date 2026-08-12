<?php

namespace App\Services\Fetcher;

/**
 * Prevents the crawler being used as an SSRF vector — e.g. a "competitor URL"
 * field pointing at http://169.254.169.254/ (cloud metadata) or http://127.0.0.1/admin.
 */
class UrlSafetyGuard
{
    public static function isSafeToFetch(string $url): bool
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return false;
        }

        // Resolve hostname to IP(s) and check each — blocks DNS rebinding to internal IPs too.
        $ips = self::resolveAll($host);
        if (empty($ips)) {
            // Could not resolve at all — treat as unsafe rather than silently skipping.
            return false;
        }

        foreach ($ips as $ip) {
            if (self::isBlockedIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function resolveAll(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];
        $recordsA = @dns_get_record($host, DNS_A);
        $recordsAAAA = @dns_get_record($host, DNS_AAAA);

        foreach (array_merge($recordsA ?: [], $recordsAAAA ?: []) as $record) {
            if (! empty($record['ip'])) {
                $ips[] = $record['ip'];
            } elseif (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }

    private static function isBlockedIp(string $ip): bool
    {
        // Cloud metadata endpoints (AWS/GCP/Azure/DigitalOcean all use this address).
        if ($ip === '169.254.169.254') {
            return true;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // Fails = it IS a private, reserved, or loopback range.
            return true;
        }

        return false;
    }
}
