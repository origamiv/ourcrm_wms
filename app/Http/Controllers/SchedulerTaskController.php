<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\SchedulerTask;
use App\Services\EntitySyncService;
use Illuminate\Http\Request;

final class SchedulerTaskController extends BaseApiController
{
    public function index(Request $request)
    {
        return response()->json(['data' => SchedulerTask::query()->visibleTo($request->user()->tenant_id)->where('module', 'wms')->latest('id')->get()]);
    }

    public function store(Request $request, EntitySyncService $sync)
    {
        $task = $this->save($request, new SchedulerTask);
        return response()->json(['data' => $sync->current(SchedulerTask::class, $task->tenant_id, $task->id)], 201);
    }

    public function update(Request $request, int $id, EntitySyncService $sync)
    {
        $task = SchedulerTask::query()->visibleTo($request->user()->tenant_id)->findOrFail($id);
        $this->checkVersion($request, $task, $sync);
        $task = $this->save($request, $task);
        return response()->json(['data' => $sync->current(SchedulerTask::class, $task->tenant_id, $task->id)]);
    }

    public function destroy(Request $request, int $id, EntitySyncService $sync)
    {
        $task = SchedulerTask::query()->visibleTo($request->user()->tenant_id)->findOrFail($id);
        $this->checkVersion($request, $task, $sync); $task->delete();
        return response()->json(['data' => $sync->current(SchedulerTask::class, $task->tenant_id, $task->id)]);
    }

    private function save(Request $request, SchedulerTask $task): SchedulerTask
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'shortname' => ['required', 'regex:/^[a-z0-9_]+$/', 'max:255'], 'task_type' => ['required', 'in:command,job'], 'target' => ['required', 'string', 'max:255'], 'options' => ['required', 'array'], 'status' => ['sometimes', 'integer', 'in:0,1'], 'version' => ['sometimes', 'string']]);
        $task->fill($data + ['module' => 'wms', 'tenant_id' => $request->user()->tenant_id, 'status' => (int) ($data['status'] ?? 1)]);
        $task->save();
        return $task->fresh();
    }

    private function checkVersion(Request $request, SchedulerTask $task, EntitySyncService $sync): void
    {
        $current = $sync->current(SchedulerTask::class, $task->tenant_id, $task->id);
        abort_unless(isset($request->version) && hash_equals((string) $current['version'], (string) $request->version), 409, 'Запись уже изменена. Загрузите актуальные данные.');
    }
}
