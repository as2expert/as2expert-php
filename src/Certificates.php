<?php

declare(strict_types=1);

namespace As2Expert;

final class Certificates extends AbstractResource
{
    /** @return list<array<string,mixed>> */
    public function list(): array
    {
        return $this->collection('/certificates', []);
    }

    /** @return array<string,mixed> */
    public function get(mixed $id): array
    {
        return $this->object('/certificates/detail', ['id' => $id]);
    }

    /**
     * @param array<string,mixed> $cert
     * @return array<string,mixed>
     */
    public function create(array $cert): array
    {
        return $this->object('/certificates/create', $cert);
    }
}
