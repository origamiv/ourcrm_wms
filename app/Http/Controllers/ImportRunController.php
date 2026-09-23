<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Services\QueryFilterService;
use App\Services\QuerySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ImportRunController extends BaseApiController
{
    /** Возвращает историю запусков импортов текущего tenant с прогрессом обработки. */
    public function index(Request $request, QueryFilterService $filters, QuerySearchService $search): JsonResponse
    {
        $input = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'filter' => ['sometimes', 'nullable', 'string', 'max:32768'],
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'search_fields' => ['sometimes', 'array', 'min:1'],
            'search_fields.*' => ['string', \Illuminate\Validation\Rule::in(['id', 'name', 'client_id', 'client_name', 'marketplace', 'webhook_id', 'started_at', 'updated_at', 'current_stage', 'processed_records', 'status', 'error_message'])],
            'search_mode' => ['sometimes', \Illuminate\Validation\Rule::in(['filter', 'highlight'])],
            'search_index' => ['sometimes', 'integer', 'min:0'],
        ]);
        $query = ImportRun::query()
            ->with('stages')
            ->visibleTo((string) $request->user()->tenant_id);
        $filterFields = [
            'id' => 'wms.import_runs.id',
            'name' => 'wms.import_runs.name',
            'client_id' => "COALESCE(wms.import_runs.source_client_id, NULLIF(wms.import_runs.options->>'client_id', '')::bigint)",
            'client_name' => "wms.import_runs.options->>'client_name'",
            'marketplace' => "COALESCE(wms.import_runs.options->>'marketplace', wms.import_runs.source_system)",
            'webhook_id' => "COALESCE(wms.import_runs.source_webhook_id, NULLIF(wms.import_runs.options->>'webhook_id', '')::bigint)",
            'started_at' => 'wms.import_runs.started_at',
            'updated_at' => 'wms.import_runs.updated_at',
            'current_stage' => 'wms.import_runs.current_stage',
            'processed_records' => 'wms.import_runs.processed_records',
            'status' => 'wms.import_runs.status',
            'error_message' => 'wms.import_runs.error_message',
        ];
        $mergedFilter = $search->merge(
            $input['filter'] ?? null,
            $input['search'] ?? null,
            $input['search_fields'] ?? ['id'],
            array_keys($filterFields),
            $input['search_mode'] ?? 'filter',
            ['status' => ['queued' => 'В очереди', 'running' => 'Выполняется', 'completed' => 'Завершён', 'failed' => 'Ошибка']],
            ['started_at', 'updated_at'],
        );
        $filters->apply($query, $mergedFilter, $filterFields);
        $runs = $query
            ->orderByRaw('GREATEST(wms.import_runs.updated_at, COALESCE((SELECT MAX(stages.updated_at) FROM wms.import_run_stages AS stages WHERE stages.import_run_id = wms.import_runs.id), wms.import_runs.updated_at)) DESC')
            ->orderByDesc('id')
            ->paginate(50);

        $data = collect($runs->items())->flatMap(function (ImportRun $run): array {
            $current = $run->stages->firstWhere('status', 'running') ?: $run->stages->firstWhere('stage_key', $run->current_stage);
            $parent = [
                'row_type' => 'run',
                'id' => (string) $run->id,
                'parent_id' => null,
                'name' => $run->name ?: 'Импорт данных',
                'project' => $run->project ?: $run->source_system,
                'updated_at' => $run->updated_at?->toISOString(),
                'started_at' => $run->started_at?->toISOString(),
                'total_records' => (int) $run->total_records,
                'total_chunks' => (int) $run->total_chunks,
                'processed_records' => (int) $run->processed_records,
                'processed_chunks' => (int) $run->processed_chunks,
                'total_stages' => (int) $run->total_stages,
                'completed_stages' => (int) $run->completed_stages,
                'status' => (string) $run->status,
                'current_stage' => $current?->stage_key ?: $run->current_stage,
                'current_stage_number' => $current?->stage_number,
                'current_stage_name' => $current?->name,
                'error_message' => $run->error_message,
                'webhook_id' => $run->source_webhook_id ?: ($run->options['webhook_id'] ?? null),
                'client_id' => $run->source_client_id ?: ($run->options['client_id'] ?? null),
                'client_name' => $run->options['client_name'] ?? null,
                'account_id' => $run->options['account_id'] ?? null,
                'marketplace' => $run->options['marketplace'] ?? $run->source_system,
            ];
            $showStages = $run->project === 'tswms'
                && (array) ($run->options['only'] ?? []) === [];
            $stages = $showStages ? $run->stages->map(fn (ImportRunStage $stage): array => [
                'row_type' => 'stage',
                'id' => (string) $stage->id,
                'parent_id' => (string) $run->id,
                'name' => $stage->name,
                'project' => $run->project ?: $run->source_system,
                'updated_at' => $stage->updated_at?->toISOString(),
                'started_at' => $stage->started_at?->toISOString(),
                'total_records' => (int) $stage->total_records,
                'total_chunks' => (int) $stage->total_chunks,
                'processed_records' => (int) $stage->processed_records,
                'processed_chunks' => (int) $stage->processed_chunks,
                'total_stages' => null,
                'completed_stages' => null,
                'status' => (string) $stage->status,
                'current_stage' => $stage->stage_key,
                'current_stage_number' => (int) $stage->stage_number,
                'current_stage_name' => $stage->name,
                'error_message' => $stage->error_message,
                'webhook_id' => $run->source_webhook_id ?: ($run->options['webhook_id'] ?? null),
                'client_id' => $run->source_client_id ?: ($run->options['client_id'] ?? null),
                'client_name' => $run->options['client_name'] ?? null,
                'account_id' => $run->options['account_id'] ?? null,
                'marketplace' => $run->options['marketplace'] ?? $run->source_system,
            ])->all() : [];

            return [$parent, ...$stages];
        })->values();

        return response()->json([
            'data' => $data,
            'current_page' => $runs->currentPage(),
            'last_page' => $runs->lastPage(),
            'total' => $runs->total(),
        ]);
    }

    /** Возвращает статус периодических задач TSWMS планировщика для текущего tenant */
    public function schedulerStatus(Request $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        // Получаем задачи TSWMS планировщика для текущего тенанта
        $schedulerTasks = \DB::table('public.scheduler as s')
            ->join('public.scheduler_tasks as st', 'st.shortname', '=', 's.task_key')
            ->where('s.tenant_id', $tenantId)
            ->where('st.target', 'wms:import:tswms-scheduled')
            ->where('s.status', 1)
            ->select([
                's.id',
                's.name',
                's.task_key', 
                's.status as schedule_status',
                's.next_run_at',
                's.last_run_at',
                'st.options->\'entity_groups\' as entity_groups',
                'st.options->\'description\' as description',
            ])
            ->orderBy('s.task_key')
            ->get();

        // Получаем последние запуски импортов TSWMS
        $recentImports = ImportRun::query()
            ->where('tenant_id', $tenantId)
            ->where('project', 'tswms')
            ->where('started_at', '>=', now()->subHours(24))
            ->select(['id', 'name', 'status', 'started_at', 'finished_at', 'error_message', 'options'])
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        // Группируем импорты по группам сущностей
        $importsByGroup = [];
        foreach ($recentImports as $import) {
            $options = is_string($import->options) ? json_decode($import->options, true) : (array)($import->options ?? []);
            $only = $options['only'] ?? [];
            
            // Определяем группу по импортированным сущностям
            $group = $this->determineEntityGroup($only);
            if (!isset($importsByGroup[$group])) {
                $importsByGroup[$group] = [];
            }
            $importsByGroup[$group][] = [
                'id' => $import->id,
                'name' => $import->name,
                'status' => $import->status,
                'started_at' => $import->started_at?->toISOString(),
                'finished_at' => $import->finished_at?->toISOString(),
                'error_message' => $import->error_message,
            ];
        }

        return response()->json([
            'scheduler_tasks' => $schedulerTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'task_key' => $task->task_key,
                    'entity_groups' => json_decode($task->entity_groups ?? '""', true),
                    'description' => json_decode($task->description ?? '""', true),
                    'schedule_status' => $task->schedule_status,
                    'next_run_at' => $task->next_run_at,
                    'last_run_at' => $task->last_run_at,
                ];
            }),
            'recent_imports_by_group' => $importsByGroup,
            'current_time' => now()->toISOString(),
        ]);
    }

    private function determineEntityGroup(array $entities): string
    {
        $entityGroups = [
            'references' => ['clients', 'services', 'warehouses', 'task_stages', 'users', 'accounts', 'webhooks', 'documents'],
            'goods' => ['goods'],
            'orders' => ['orders', 'order_goods', 'order_histories', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'],
            'tasks' => ['tasks', 'task_goods', 'acceptances', 'cell_goods'],
        ];

        foreach ($entityGroups as $group => $groupEntities) {
            if (array_intersect($entities, $groupEntities)) {
                return $group;
            }
        }

        return 'unknown';
    }
}
