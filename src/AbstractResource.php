<?php

declare(strict_types=1);

namespace As2Expert;

abstract class AbstractResource
{
    public function __construct(protected Transport $transport) {}

    /**
     * @param array<string,mixed> $body
     * @param array<string,string> $headers
     * @return array<string,mixed>
     */
    protected function object(string $path, array $body = [], array $headers = []): array
    {
        $data = $this->transport->post($path, $body, $headers);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string,mixed> $body
     * @return list<array<string,mixed>>
     */
    protected function collection(string $path, array $body = []): array
    {
        $data = $this->transport->post($path, $body);
        if (!is_array($data)) {
            return [];
        }
        // Already a list?
        return array_is_list($data) ? $data : [$data];
    }
}
