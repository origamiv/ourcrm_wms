<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportRunCoordinatorJob;
use App\Jobs\SyncMarketplaceCatalogJob;

final class SchedulerTaskRegistry
{
    public function all(): array
    {
        return [
            ['key' => 'integration:sync-catalog', 'label' => 'Синхронизация каталога маркетплейса', 'type' => 'command', 'command' => 'integration:sync-catalog'],
            ['key' => 'wms:import:tswms', 'label' => 'Импорт интеграций из TSWMS', 'type' => 'command', 'command' => 'wms:import:tswms'],
            ['key' => 'job:sync-marketplace-catalog', 'label' => 'Задача: синхронизация каталога маркетплейса', 'type' => 'job', 'class' => SyncMarketplaceCatalogJob::class],
            ['key' => 'job:import-run-coordinator', 'label' => 'Задача: обработка импорта TSWMS', 'type' => 'job', 'class' => ImportRunCoordinatorJob::class],
        ];
    }

    public function find(string $key): ?array
    {
        foreach ($this->all() as $task) {
            if ($task['key'] === $key) return $task;
        }
        return null;
    }
}
