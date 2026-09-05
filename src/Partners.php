<?php

declare(strict_types=1);

namespace As2Expert;

final class Partners extends AbstractResource
{
    /**
     * @param array<string,mixed> $filter
     * @return list<array<string,mixed>>
     */
    public function list(array $filter = []): array
    {
        return $this->collection('/partners', $filter);
    }

    /** @return array<string,mixed> */
    public function get(mixed $id): array
    {
        return $this->object('/partners/detail', ['id' => $id]);
    }

    /**
     * @param array<string,mixed> $partner
     * @return array<string,mixed>
     */
    public function create(array $partner): array
    {
        return $this->object('/partners/create', $partner);
    }

    /**
     * Update a partner's identity fields.
     * @param array<string,mixed> $fields
     * @return array<string,mixed>
     */
    public function update(mixed $id, array $fields): array
    {
        return $this->object('/partners/update', ['id' => $id] + $fields);
    }

    /** @return array<string,mixed> */
    public function delete(mixed $id): array
    {
        return $this->object('/partners/delete', ['id' => $id]);
    }
}
