<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Services\BackgroundProcessStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class BackgroundProcessController extends BaseApiController
{
    /** Возвращает агрегированную статистику фоновых запусков текущей организации. */
    public function __invoke(Request $request, BackgroundProcessStatisticsService $statistics): JsonResponse
    {
        $input = $request->validate([
            'group_by' => ['sometimes', Rule::in(['clients', 'webhooks'])],
            'period' => ['sometimes', Rule::in(['today', 'yesterday', 'week', 'month', 'hours_4', 'hour', 'minutes_15'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $result = $statistics->statistics(
            (string) $request->user()->tenant_id,
            (string) ($input['group_by'] ?? 'clients'),
            (string) ($input['period'] ?? 'today'),
            (int) ($input['page'] ?? 1),
        );

        return response()->json($result)->header('Cache-Control', 'private, no-store');
    }
}
