<?php

declare(strict_types=1);

namespace As2Expert;

/** 400 / 422 — the request failed server-side validation. */
class ValidationException extends ApiException
{
    /**
     * @param array<string,mixed>|null $payload
     * @param list<mixed>              $fields
     */
    public function __construct(
        string $message,
        ?int $status = null,
        ?string $errorCode = null,
        ?array $payload = null,
        public readonly array $fields = [],
    ) {
        parent::__construct($message, $status, $errorCode, $payload);
    }
}
