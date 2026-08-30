<?php

declare(strict_types=1);

namespace As2Expert;

/**
 * The AS2Expert API client.
 *
 * Every method maps to a single POST call (the API is POST-only) and returns the
 * response `data` (an associative array, or a list for collections). Failures
 * throw a subclass of {@see ApiException}.
 *
 * ```php
 * $client = new As2Expert\Client(['token' => '...', 'environment' => 'free']);
 * $out = $client->edifact->convert("UNB+...'", 'json');
 * echo $out['filename'];
 * ```
 */
final class Client
{
    /** Convenience host presets. */
    public const ENVIRONMENTS = [
        'free' => 'https://free.as2expert.com/api/v1',
        'b2b' => 'https://b2b.as2expert.com/api/v1',
    ];

    public readonly Messages $messages;
    public readonly Partners $partners;
    public readonly Certificates $certificates;
    public readonly Stations $stations;
    public readonly WebhooksResource $webhooks;
    public readonly BusinessDocuments $businessDocuments;
    public readonly Edifact $edifact;
    public readonly Dashboard $dashboard;

    private Transport $transport;

    /**
     * @param array{
     *     token?: string,
     *     base_url?: string,
     *     environment?: string,
     *     timeout?: float,
     *     max_retries?: int,
     *     verify_tls?: bool,
     *     user_agent?: string,
     *     transport?: Transport
     * } $config
     */
    public function __construct(array $config)
    {
        if (isset($config['transport']) && $config['transport'] instanceof Transport) {
            $this->transport = $config['transport'];
        } else {
            $baseUrl = $config['base_url'] ?? '';
            if ($baseUrl === '' && isset($config['environment'])) {
                $baseUrl = self::ENVIRONMENTS[$config['environment']] ?? '';
            }
            if ($baseUrl === '') {
                throw new ApiException(
                    "Provide 'base_url', or 'environment' one of: " . implode(', ', array_keys(self::ENVIRONMENTS))
                );
            }
            $this->transport = new CurlTransport(
                token: $config['token'] ?? '',
                baseUrl: $baseUrl,
                timeout: $config['timeout'] ?? 30.0,
                maxRetries: $config['max_retries'] ?? 2,
                verifyTls: $config['verify_tls'] ?? true,
                userAgent: $config['user_agent'] ?? 'as2expert-php/0.1.0',
            );
        }

        $this->messages = new Messages($this->transport);
        $this->partners = new Partners($this->transport);
        $this->certificates = new Certificates($this->transport);
        $this->stations = new Stations($this->transport);
        $this->webhooks = new WebhooksResource($this->transport);
        $this->businessDocuments = new BusinessDocuments($this->transport);
        $this->edifact = new Edifact($this->transport);
        $this->dashboard = new Dashboard($this->transport);
    }

    public function baseUrl(): string
    {
        return $this->transport->baseUrl();
    }
}
