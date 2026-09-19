<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\CreateSchedulerRequest;
use App\Http\Requests\UpdateSchedulerRequest;
use App\Models\Scheduler;
use App\Services\SchedulerScheduleService;
use App\Services\SchedulerTaskRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SchedulerController extends BaseApiController
{
    public function index(Request $request, SchedulerScheduleService $schedules, SchedulerTaskRegistry $registry): JsonResponse
    {
        $rows = Scheduler::query()->visibleTo($request->user()->tenant_id)->where('module', 'wms')->latest('id')->get()->map(fn (Scheduler $item): array => $this->row($item, $schedules, $registry))->values();
        return response()->json(['data' => $rows]);
    }

    public function tasks(): JsonResponse
    {
        return response()->json(['data' => app(SchedulerTaskRegistry::class)->all()]);
    }

    public function store(CreateSchedulerRequest $request, SchedulerScheduleService $schedules, SchedulerTaskRegistry $registry): JsonResponse
    {
        return $this->save($request->validated(), $request, $schedules, $registry);
    }

    public function update(UpdateSchedulerRequest $request, int $id, SchedulerScheduleService $schedules, SchedulerTaskRegistry $registry): JsonResponse
    {
        $item = $this->find($request, $id);
        return $this->save($request->validated(), $request, $schedules, $registry, $item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->find($request, $id)->delete();
        return response()->json(['data' => true]);
    }

    public function runs(Request $request, int $id): JsonResponse
    {
        $item = $this->find($request, $id);
        return response()->json(['data' => $item->runs()->latest('id')->get()]);
    }

    private function save(array $data, Request $request, SchedulerScheduleService $schedules, SchedulerTaskRegistry $registry, ?Scheduler $item = null): JsonResponse
    {
        if (! $registry->find($data['task_key'])) throw ValidationException::withMessages(['task_key' => 'Выбранная задача не зарегистрирована.']);
        try { $schedule = $schedules->normalize($data['schedule']); } catch (\InvalidArgumentException $exception) { throw ValidationException::withMessages(['schedule' => $exception->getMessage()]); }
        $task = $registry->find($data['task_key']);
        $values = ['name' => $data['name'], 'module' => 'wms', 'tenant_id' => $request->user()->tenant_id, 'task_key' => $data['task_key'], 'task_type' => $task['type'], 'params' => $data['params'] ?? [], 'schedule' => $schedule, 'status' => (int) ($data['status'] ?? 1)];
        $values['next_run_at'] = $values['status'] ? $schedules->next($schedule) : null;
        if ($item) $item->update($values); else $item = Scheduler::query()->create($values);
        return response()->json(['data' => $this->row($item->fresh(), $schedules, $registry)]);
    }

    private function find(Request $request, int $id): Scheduler
    {
        return Scheduler::query()->visibleTo($request->user()->tenant_id)->where('module', 'wms')->findOrFail($id);
    }

    private function row(Scheduler $item, SchedulerScheduleService $schedules, SchedulerTaskRegistry $registry): array
    {
        return $item->toArray() + ['task_label' => $registry->find($item->task_key)['label'] ?? $item->task_key, 'schedule_label' => $schedules->label($item->schedule)];
    }
}
