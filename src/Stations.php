<?php

declare(strict_types=1);

namespace As2Expert;

final class Stations extends AbstractResource
{
    /**
     * @param array<string,mixed> $filter
     * @return list<array<string,mixed>>
     */
    public function list(array $filter = []): array
    {
        return $this->collection('/stations', $filter);
    }

    /** @return array<string,mixed> */
    public function get(mixed $id): array
    {
        return $this->object('/stations/detail', ['id' => $id]);
    }

    /** @return array<string,mixed> */
    public function stats(mixed $id): array
    {
        return $this->object('/stations/stats', ['id' => $id]);
    }

    /**
     * @param array<string,mixed> $station
     * @return array<string,mixed>
     */
    public function create(array $station): array
    {
        return $this->object('/stations/create', $station);
    }

    /**
     * Update a station's identity fields.
     * @param array<string,mixed> $fields
     * @return array<string,mixed>
     */
    public function update(mixed $id, array $fields): array
    {
        return $this->object('/stations/update', ['id' => $id] + $fields);
    }

    /** @return array<string,mixed> */
    public function delete(mixed $id): array
    {
        return $this->object('/stations/delete', ['id' => $id]);
    }
}
