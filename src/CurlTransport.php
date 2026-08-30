<?php

declare(strict_types=1);

namespace As2Expert;

/**
 * libcurl-backed {@see Transport}, retrying 429/5xx and transient failures.
 */
final class CurlTransport implements Transport
{
    private string $baseUrl;

    public function __construct(
        private string $token,
        string $baseUrl,
        private float $timeout = 30.0,
        private int $maxRetries = 2,
        private bool $verifyTls = true,
        private string $userAgent = 'as2expert-php/0.1.0',
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        if ($this->baseUrl === '') {
            throw new ApiException('base URL is required');
        }
        if ($this->token === '') {
            throw new ApiException('token is required');
        }
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function post(string $path, array $body, array $headers = []): mixed
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            throw new TransportException('failed to encode request body');
        }

        $hdr = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token,
        ];
        foreach ($headers as $k => $v) {
            $hdr[] = $k . ': ' . $v;
        }

        for ($attempt = 0; ; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => $hdr,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
                CURLOPT_SSL_VERIFYPEER => $this->verifyTls,
                CURLOPT_SSL_VERIFYHOST => $this->verifyTls ? 2 : 0,
            ]);
            $resp = curl_exec($ch);
            $errno = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $errmsg = curl_error($ch);
            // curl_close() is a no-op since PHP 8.0 and deprecated in 8.5; the
            // handle is freed when $ch goes out of scope.
            unset($ch);

            if ($errno !== 0) {
                $transient = in_array($errno, [
                    CURLE_OPERATION_TIMEOUTED,
                    CURLE_COULDNT_CONNECT,
                    CURLE_GOT_NOTHING,
                    CURLE_RECV_ERROR,
                    CURLE_SEND_ERROR,
                ], true);
                if ($transient && $attempt < $this->maxRetries) {
                    $this->backoff($attempt);
                    continue;
                }
                throw new TransportException('curl error: ' . $errmsg);
            }

            /** @var array<string,mixed>|null $parsed */
            $parsed = is_string($resp) ? json_decode($resp, true) : null;
            if (!is_array($parsed)) {
                $parsed = null;
            }

            if ($status >= 200 && $status < 300) {
                return $this->unwrapData($parsed);
            }
            if (($status === 429 || $status >= 500) && $attempt < $this->maxRetries) {
                $this->backoff($attempt);
                continue;
            }
            throw self::errorForStatus($status, $this->messageOf($parsed, is_string($resp) ? $resp : ''), $parsed);
        }
    }

    /** @param array<string,mixed>|null $parsed */
    private function unwrapData(?array $parsed): mixed
    {
        if ($parsed !== null && array_key_exists('data', $parsed)) {
            return $parsed['data'];
        }
        return $parsed;
    }

    /** @param array<string,mixed>|null $parsed */
    private function messageOf(?array $parsed, string $fallback): string
    {
        if ($parsed !== null) {
            foreach (['msg', 'message', 'error'] as $k) {
                if (isset($parsed[$k]) && is_string($parsed[$k]) && $parsed[$k] !== '') {
                    return $parsed[$k];
                }
            }
        }
        if ($fallback !== '') {
            return substr($fallback, 0, 300);
        }
        return 'request failed';
    }

    private function backoff(int $attempt): void
    {
        $ms = min(200 * (1 << $attempt), 4000);
        usleep($ms * 1000);
    }

    /** @param array<string,mixed>|null $payload */
    public static function errorForStatus(int $status, string $message, ?array $payload): ApiException
    {
        $code = is_array($payload) && isset($payload['code']) && is_string($payload['code'])
            ? $payload['code'] : null;

        return match (true) {
            $status === 401, $status === 403 => new AuthException($message, $status, $code, $payload),
            $status === 400, $status === 422 => new ValidationException(
                $message, $status, $code, $payload,
                is_array($payload) && isset($payload['fields']) && is_array($payload['fields'])
                    ? $payload['fields'] : [],
            ),
            $status === 404 => new NotFoundException($message, $status, $code, $payload),
            $status === 429 => new RateLimitException(
                $message, $status, $code, $payload,
                is_array($payload) && isset($payload['retry_after']) && is_numeric($payload['retry_after'])
                    ? (float) $payload['retry_after'] : null,
            ),
            $status >= 500 => new ServerException($message, $status, $code, $payload),
            default => new ApiException($message, $status, $code, $payload),
        };
    }
}
