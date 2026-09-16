<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\ImportRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ImportRunController extends BaseApiController
{
    private const STAGES = [
        'clients' => 'Клиенты',
        'accounts' => 'Аккаунты',
        'webhooks' => 'Вебхуки',
        'goods' => 'Товары',
        'warehouses' => 'Склады',
        'services' => 'Сервисы',
        'documents' => 'Документы',
        'task_stages' => 'Этапы задач',
        'users' => 'Пользователи',
        'tasks' => 'Задачи',
        'task_goods' => 'Товары задач',
        'acceptances' => 'Приемки',
        'cell_goods' => 'Размещения',
    ];

    /** Возвращает историю запусков импортов текущего tenant с прогрессом обработки. */
    public function index(Request $request): JsonResponse
    {
        $runs = ImportRun::query()
            ->visibleTo((string) $request->user()->tenant_id)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(50);

        return response()->json([
            'data' => collect($runs->items())->map(fn (ImportRun $run): array => [
                'id' => (string) $run->id,
                'name' => $run->name ?: 'Импорт данных',
                'project' => $run->project ?: $run->source_system,
                'started_at' => $run->started_at?->toISOString(),
                'total_records' => (int) $run->total_records,
                'total_chunks' => (int) $run->total_chunks,
                'processed_records' => (int) $run->processed_records,
                'processed_chunks' => (int) $run->processed_chunks,
                'total_stages' => (int) $run->total_stages,
                'status' => (string) $run->status,
                'current_stage' => $run->current_stage,
                'current_stage_number' => $run->current_stage ? array_search($run->current_stage, array_keys(self::STAGES), true) + 1 : null,
                'current_stage_name' => $run->current_stage ? (self::STAGES[$run->current_stage] ?? $run->current_stage) : null,
                'error_message' => $run->error_message,
            ])->values(),
            'current_page' => $runs->currentPage(),
            'last_page' => $runs->lastPage(),
            'total' => $runs->total(),
        ]);
    }
}
