<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchedulerTask;

final class SchedulerTaskRegistry
{
    public function all(string $tenant): array
    {
        $this->ensureDefaults($tenant);
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

    public function ensureDefaults(string $tenant): void
    {
        $defaults = [
            ['shortname' => 'integration_sync_catalog', 'name' => 'Синхронизация каталога маркетплейса', 'task_type' => 'command', 'target' => 'integration:sync-catalog', 'options' => ['required' => ['webhook_id' => ['type' => 'integer', 'label' => 'ID интеграции']], 'optional' => ['tenant' => ['type' => 'string', 'label' => 'Тенант', 'default' => 'current_tenant'], 'sync' => ['type' => 'boolean', 'label' => 'Выполнить синхронно']]]],
            ['shortname' => 'wms_import_tswms', 'name' => 'Импорт интеграций из TSWMS', 'task_type' => 'command', 'target' => 'wms:import:tswms', 'options' => ['required' => [], 'optional' => ['tenant' => ['type' => 'string', 'label' => 'Тенант', 'default' => 'current_tenant'], 'tswms-client-id' => ['type' => 'string', 'label' => 'ID клиента TSWMS'], 'only' => ['type' => 'string', 'label' => 'Этапы через запятую']]]],
            ['shortname' => 'job_sync_marketplace_catalog', 'name' => 'Задача: синхронизация каталога маркетплейса', 'task_type' => 'job', 'target' => 'App\\Jobs\\SyncMarketplaceCatalogJob', 'options' => ['required' => ['webhookId' => ['type' => 'integer', 'label' => 'ID интеграции']], 'optional' => ['tenant' => ['type' => 'string', 'label' => 'Тенант', 'default' => 'current_tenant']]]],
            ['shortname' => 'job_import_run_coordinator', 'name' => 'Задача: обработка импорта TSWMS', 'task_type' => 'job', 'target' => 'App\\Jobs\\ImportRunCoordinatorJob', 'options' => ['required' => ['importId' => ['type' => 'integer', 'label' => 'ID импорта']], 'optional' => []]],
        ];
        foreach ($defaults as $default) SchedulerTask::query()->firstOrCreate(['tenant_id' => $tenant, 'shortname' => $default['shortname']], $default + ['module' => 'wms', 'status' => 1, 'tenant_id' => $tenant]);
    }
}
