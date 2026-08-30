<?php

declare(strict_types=1);

namespace As2Expert;

/**
 * Webhook configuration endpoints. (For verifying inbound signatures, see the
 * static helpers on {@see Webhooks}.)
 */
final class WebhooksResource extends AbstractResource
{
    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public function configure(array $config): array
    {
        return $this->object('/webhooks/configure', $config);
    }

    /** @return array<string,mixed> */
    public function get(): array
    {
        return $this->object('/webhooks/get', []);
    }

    /** @return array<string,mixed> */
    public function test(): array
    {
        return $this->object('/webhooks/test', []);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function logs(array $params = []): array
    {
        return $this->object('/webhooks/logs', $params);
    }
}
