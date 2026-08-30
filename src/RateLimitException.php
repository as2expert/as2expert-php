<?php

declare(strict_types=1);

namespace As2Expert;

/** 429 — too many requests. */
class RateLimitException extends ApiException
{
    /** @param array<string,mixed>|null $payload */
    public function __construct(
        string $message,
        ?int $status = null,
        ?string $errorCode = null,
        ?array $payload = null,
        public readonly ?float $retryAfter = null,
    ) {
        parent::__construct($message, $status, $errorCode, $payload);
    }
}
