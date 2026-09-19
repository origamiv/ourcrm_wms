<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchedulerTask;

final class SchedulerTaskRegistry
{
    public function all(string $tenant): array
    {
        return SchedulerTask::query()->visibleTo($tenant)->where('module', 'wms')->where('status', 1)->orderBy('name')->get()->map(fn (SchedulerTask $task): array => ['key' => $task->shortname, 'label' => $task->name, 'type' => $task->task_type, 'target' => $task->target, 'options' => $task->options])->all();
    }

    public function find(string $key, string $tenant): ?array
    {
        $key = ['integration:sync-catalog' => 'integration_sync_catalog', 'wms:import:tswms' => 'wms_import_tswms', 'job:sync-marketplace-catalog' => 'job_sync_marketplace_catalog', 'job:import-run-coordinator' => 'job_import_run_coordinator'][$key] ?? $key;
        foreach ($this->all($tenant) as $task) {
            if ($task['key'] === $key) return $task;
        }
        return null;
    }
}
