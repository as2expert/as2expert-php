<?php

declare(strict_types=1);

namespace As2Expert;

final class Dashboard extends AbstractResource
{
    /** @return array<string,mixed> */
    public function kpis(): array
    {
        return $this->object('/dashboard/kpis', []);
    }
}
