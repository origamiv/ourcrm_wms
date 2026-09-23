<?php

declare(strict_types=1);

namespace App\Contracts;

interface SyncPayloadProjector
{
    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function project(array $row, ?string $tenant): array;
}
