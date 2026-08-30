<?php

declare(strict_types=1);

namespace As2Expert;

/**
 * HTTP transport: one POST per API call. Implementations return the decoded
 * `data` field (or the whole payload when there is no `data`) and throw a
 * subclass of {@see ApiException} on failure.
 */
interface Transport
{
    /**
     * @param array<string,mixed> $body
     * @param array<string,string> $headers
     * @return mixed the decoded `data` value
     */
    public function post(string $path, array $body, array $headers = []): mixed;

    public function baseUrl(): string;
}
