<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Services\WorktimeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WorktimeController extends BaseApiController
{
    public function page(): Response { return Inertia::render('Worktime'); }

    public function state(Request $request, WorktimeService $service): JsonResponse { return response()->json($service->state($request->user())); }

    public function calendar(Request $request, WorktimeService $service): JsonResponse
    {
        $now = CarbonImmutable::now();
        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d']]);
        $from = $data['from'] ?? $now->startOfMonth()->toDateString(); $to = $data['to'] ?? $now->endOfMonth()->toDateString();
        abort_if($from > $to, 422, 'Дата начала не может быть позже даты окончания.');
        return response()->json($service->calendar($request->user(), $from, $to));
    }

    public function action(Request $request, WorktimeService $service, string $action): JsonResponse
    { abort_unless(in_array($action, ['start', 'pause', 'finish'], true), 404); return response()->json($service->event($request->user(), $action)); }
}
