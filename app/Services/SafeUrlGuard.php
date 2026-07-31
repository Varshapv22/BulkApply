<?php

namespace App\Services;

/**
 * SSRF guard for outbound requests to caller-supplied URLs (webhooks,
 * company websites). Resolves the host and rejects private/loopback/
 * reserved/link-local ranges, including cloud metadata endpoints. Callers
 * should re-check with this immediately before each outbound request
 * (not just at save time) to reduce DNS-rebinding exposure.
 */
class SafeUrlGuard
{
    public static function isSafe(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        $ip = gethostbyname($host);
        if ($ip === $host) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
