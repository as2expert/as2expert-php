<?php

declare(strict_types=1);

namespace As2Expert;

/**
 * Base class for every error raised by the client. Carries the HTTP status
 * (when the request reached the server), the parsed payload, and an optional
 * application error code.
 */
class ApiException extends \RuntimeException
{
    /** @param array<string,mixed>|null $payload */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorCode = null,
        public readonly ?array $payload = null,
    ) {
        parent::__construct($message);
    }
}
