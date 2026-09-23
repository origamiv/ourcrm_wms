<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Services\QueryFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ImportRunController extends BaseApiController
{
    /** Возвращает историю запусков импортов текущего tenant с прогрессом обработки. */
    public function index(Request $request, QueryFilterService $filters): JsonResponse
    {
        $input = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'filter' => ['sometimes', 'nullable', 'string', 'max:32768'],
        ]);
        $query = ImportRun::query()
            ->with('stages')
            ->visibleTo((string) $request->user()->tenant_id);
        $filters->apply($query, $input['filter'] ?? null, [
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
        ]);
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
}
