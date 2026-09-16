<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\ImportRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ImportRunController extends BaseApiController
{
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
                'status' => (string) $run->status,
                'current_stage' => $run->current_stage,
                'error_message' => $run->error_message,
            ])->values(),
            'current_page' => $runs->currentPage(),
            'last_page' => $runs->lastPage(),
            'total' => $runs->total(),
        ]);
    }
}
