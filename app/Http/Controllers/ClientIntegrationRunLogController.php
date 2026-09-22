<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\ImportRun;
use App\Models\IntegrationWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientIntegrationRunLogController extends BaseApiController
{
    public function calendar(Request $request, string $id): JsonResponse
    {
        $input = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);
        $tenant = (string) $request->user()->tenant_id;
        IntegrationWebhook::withTrashed()->visibleTo($tenant)->findOrFail($id);
        $year = (int) $input['year'];

        $days = $this->runs($tenant, $id)
            ->where('created_at', '>=', sprintf('%04d-01-01', $year))
            ->where('created_at', '<', sprintf('%04d-01-01', $year + 1))
            ->selectRaw('created_at::date AS date, COUNT(*)::integer AS count')
            ->groupByRaw('created_at::date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row): array => ['date' => (string) $row->date, 'count' => (int) $row->count]);

        return response()->json(['data' => $days])->header('Cache-Control', 'private, no-store');
    }

    public function day(Request $request, string $id): JsonResponse
    {
        $input = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $tenant = (string) $request->user()->tenant_id;
        IntegrationWebhook::withTrashed()->visibleTo($tenant)->findOrFail($id);
        $date = $input['date'];
        $runs = $this->runs($tenant, $id)
            ->where('created_at', '>=', $date.' 00:00:00')
            ->where('created_at', '<', date('Y-m-d', strtotime($date.' +1 day')).' 00:00:00')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(100);

        return response()->json([
            'data' => collect($runs->items())->map(fn (ImportRun $run): array => [
                'id' => (string) $run->id,
                'created_at' => $run->created_at?->toISOString(),
                'status' => (string) $run->status,
                'processed_records' => (int) $run->processed_records,
            ])->all(),
            'current_page' => $runs->currentPage(),
            'last_page' => $runs->lastPage(),
            'total' => $runs->total(),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function runs(string $tenant, string $id): \Illuminate\Database\Eloquent\Builder
    {
        return ImportRun::query()
            ->where('tenant_id', $tenant)
            ->where('source_webhook_id', $id);
    }
}
