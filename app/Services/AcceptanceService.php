<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Acceptance;
use App\Models\Cell;
use App\Models\CellGood;
use App\Models\Good;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AcceptanceService
{
    public function createForTaskIfNeeded(Task $task, string $tenant): ?Acceptance
    {
        $type = $task->task_type_id ? TaskType::withTrashed()->find($task->task_type_id) : null;
        $typeName = str_replace('ё', 'е', mb_strtolower((string) ($type?->shortname ?: $type?->name)));
        if (! $type || (! str_contains($typeName, 'прием') && $typeName !== 'receipt')) {
            return null;
        }

        $inProgress = TaskStatus::query()->where(function ($q): void {
            $q->where('shortname', 'in_progress')->orWhereRaw('lower(name) = ?', ['в работе']);
        })->value('id');
        if ($inProgress !== null && (int) $task->status_id !== (int) $inProgress) {
            return null;
        }

        $existing = Acceptance::query()->where('tenant_id', $tenant)->where('client_id', $task->client_id)->where('task_id', $task->id)->first();
        if ($existing) return $existing;
        $acceptance = new Acceptance;
        $acceptance->forceFill([
                'client_id' => $task->client_id,
                'warehouse_id' => $task->warehouse_id,
                'task_id' => $task->id,
                'plan_count' => is_array($task->src) ? ($task->src['plan_count'] ?? $task->src['planned_count'] ?? $task->src['pieces_count'] ?? $task->src['items_count'] ?? $task->fact_count ?? 0) : ($task->fact_count ?? 0),
                'fact_count' => $task->fact_count ?? 0,
                'progress' => 0,
                'type_acceptance_id' => 1,
                'status' => 0,
                'tenant_id' => $tenant,
            ])->save();
        return $acceptance;
    }

    public function pick(User $actor, int $id, string $barcode, ?int $cellId = null): array
    {
        $tenant = (string) $actor->tenant_id;
        return DB::transaction(function () use ($actor, $tenant, $id, $barcode, $cellId): array {
            $acceptance = Acceptance::query()->visibleTo($tenant)->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if((int) $acceptance->status === 1, 422, 'Приемка уже завершена.');
            $task = $acceptance->task_id ? Task::query()->visibleTo($tenant)->findOrFail($acceptance->task_id) : null;
            $allowedGoods = $task && is_array($task->src) && is_array($task->src['goods'] ?? null)
                ? collect($task->src['goods'])->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (int) $value)->filter(fn ($value) => $value > 0)->values()->all()
                : [];
            $good = Good::query()->visibleTo($tenant)->when($allowedGoods, fn ($q) => $q->whereIn('id', $allowedGoods))->where(function ($q) use ($barcode): void {
                $q->where('code', $barcode)->orWhereRaw("barcodes::jsonb @> ?::jsonb", [json_encode([$barcode])]);
            })->first();
            abort_unless($good, 422, $allowedGoods ? 'Товар с таким ШК отсутствует в этой приемке.' : 'Товар с таким штрихкодом не найден.');

            $cell = $cellId ? Cell::query()->visibleTo($tenant)->whereKey($cellId)->first() : Cell::query()->visibleTo($tenant)->where('warehouse_id', $acceptance->warehouse_id)->orderBy('id')->first();
            abort_unless($cell && (int) $cell->warehouse_id === (int) $acceptance->warehouse_id, 422, 'Ячейка для размещения не найдена.');

            $placement = CellGood::query()->visibleTo($tenant)->where('warehouse_id', $acceptance->warehouse_id)->where('cell_id', $cell->id)->where('good_id', $good->id)->first();
            if ($placement) {
                $placement->forceFill(['cnt' => (int) $placement->cnt + 1, 'put_at' => now(), 'leave_at' => null, 'user_id' => $actor->id])->save();
            } else {
                $placement = new CellGood;
                $placement->forceFill(['warehouse_id' => $acceptance->warehouse_id, 'cell_id' => $cell->id, 'good_id' => $good->id, 'cnt' => 1, 'put_at' => now(), 'user_id' => $actor->id, 'tenant_id' => $tenant, 'src' => ['acceptance_id' => $acceptance->id, 'client_id' => $acceptance->client_id]])->save();
            }

            $fact = (int) $acceptance->fact_count + 1;
            $plan = (int) ($acceptance->plan_count ?? 0);
            $done = $plan > 0 && $fact >= $plan;
            $acceptance->forceFill(['fact_count' => $fact, 'progress' => $plan > 0 ? min(100, (int) round($fact / $plan * 100)) : 0, 'status' => $done ? 1 : $acceptance->status, 'finished_at' => $done ? now() : null])->save();
            if ($task) {
                $task->forceFill(['fact_count' => $fact, 'status' => 1]);
                if ($done) {
                    $completed = TaskStatus::query()->where(function ($q): void {
                        $q->where('shortname', 'completed')->orWhereRaw('lower(name) = ?', ['завершена']);
                    })->value('id');
                    if ($completed !== null) $task->status_id = $completed;
                    $task->completed_at = now();
                }
                $task->save();
            }
            return ['acceptance' => app(EntitySyncService::class)->current(Acceptance::class, $tenant, $acceptance->id), 'task' => $task ? app(EntitySyncService::class)->current(Task::class, $tenant, $task->id) : null, 'placement' => app(EntitySyncService::class)->current(CellGood::class, $tenant, $placement->id), 'good_id' => $good->id];
        });
    }
}
