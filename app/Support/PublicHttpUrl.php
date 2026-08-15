<?php

namespace App\Support;

final class PublicHttpUrl
{
    public static function allows(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (($parts['port'] ?? 443) !== 443) {
            return false;
        }

        return self::hostIsPublic((string) ($parts['host'] ?? ''));
    }

    public static function hostIsPublic(string $host): bool
    {
        $host = strtolower(trim($host, " \t\n\r\0\x0B[]"));

        if ($host === '' || $host === 'localhost' || $host === 'metadata.google.internal') {
            return false;
        }

        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::ipIsPublic($host);
        }

        if (preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $host) !== 1 || str_contains($host, '..')) {
            return false;
        }

        if (! app()->environment('testing')) {
            $ips = gethostbynamel($host);

            if ($ips === false || $ips === []) {
                return false;
            }

            foreach ($ips as $ip) {
                if (! self::ipIsPublic($ip)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function ipIsPublic(string $ip): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | $flags) === $ip) {
            return $ip !== '0.0.0.0';
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | $flags) === $ip
            && ! in_array($ip, ['::', '::1'], true);
    }
}
