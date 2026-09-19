<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\SchedulerTask;
use App\Services\SchedulerTaskRegistry;
use Illuminate\Http\Request;

final class SchedulerTaskController extends BaseApiController
{
    public function index(Request $request, SchedulerTaskRegistry $registry)
    {
        $registry->ensureDefaults($request->user()->tenant_id);
        return response()->json(['data' => SchedulerTask::query()->visibleTo($request->user()->tenant_id)->where('module', 'wms')->latest('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => $this->save($request, new SchedulerTask)], 201);
    }

    public function update(Request $request, int $id)
    {
        return response()->json(['data' => $this->save($request, SchedulerTask::query()->visibleTo($request->user()->tenant_id)->findOrFail($id))]);
    }

    public function destroy(Request $request, int $id)
    {
        SchedulerTask::query()->visibleTo($request->user()->tenant_id)->findOrFail($id)->delete();
        return response()->json(['data' => true]);
    }

    private function save(Request $request, SchedulerTask $task): SchedulerTask
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'shortname' => ['required', 'regex:/^[a-z0-9_]+$/', 'max:255'], 'task_type' => ['required', 'in:command,job'], 'target' => ['required', 'string', 'max:255'], 'options' => ['required', 'array'], 'status' => ['sometimes', 'integer', 'in:0,1']]);
        $task->fill($data + ['module' => 'wms', 'tenant_id' => $request->user()->tenant_id, 'status' => (int) ($data['status'] ?? 1)]);
        $task->save();
        return $task->fresh();
    }
}
