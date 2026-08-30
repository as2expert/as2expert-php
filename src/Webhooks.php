<?php

declare(strict_types=1);

namespace As2Expert;

/**
 * Verify inbound AS2Expert webhook signatures.
 *
 * AS2Expert signs deliveries with HMAC-SHA256 over "<timestamp>.<body>", sent in
 * the headers X-AS2Expert-Timestamp and X-AS2Expert-Signature ("sha256=<hex>").
 */
final class Webhooks
{
    /** Default signature freshness window, in seconds (5 minutes). */
    public const DEFAULT_TOLERANCE_SECS = 300;

    /** Compute the "sha256=<hex>" signature for a timestamp + body. */
    public static function sign(string $secret, string $timestamp, string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    /**
     * Verify a webhook signature and freshness.
     *
     * @param int|null $now current unix time in seconds (defaults to time())
     */
    public static function verify(
        string $secret,
        string $timestamp,
        string $body,
        string $signature,
        int $toleranceSecs = self::DEFAULT_TOLERANCE_SECS,
        ?int $now = null,
    ): bool {
        if ($secret === '' || $signature === '') {
            return false;
        }
        $trimmed = trim($timestamp);
        if ($trimmed === '' || !preg_match('/^-?\d+$/', $trimmed)) {
            return false;
        }
        $ts = (int) $trimmed;
        $current = $now ?? time();
        if (abs($current - $ts) > $toleranceSecs) {
            return false;
        }
        $expected = self::sign($secret, $timestamp, $body);
        $provided = str_starts_with($signature, 'sha256=') ? $signature : ('sha256=' . $signature);
        return hash_equals($expected, $provided);
    }
}
