<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Services\BackgroundProcessStatisticsService;
use App\Services\QuerySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

final class BackgroundProcessController extends BaseApiController
{
    /** Возвращает агрегированную статистику фоновых запусков текущей организации. */
    public function __invoke(Request $request, BackgroundProcessStatisticsService $statistics, QuerySearchService $search): JsonResponse
    {
        $input = $request->validate([
            'group_by' => ['sometimes', Rule::in(['clients', 'webhooks'])],
            'period' => ['sometimes', Rule::in(['today', 'yesterday', 'week', 'month', 'hours_4', 'hour', 'minutes_15'])],
            'marketplace' => ['sometimes', Rule::in(['all', 'none', 'wildberries', 'ozon', 'yandex_market'])],
            'filter' => ['sometimes', 'nullable', 'string', 'max:32768'],
            ...$this->searchRules(),
        ]);

        $filter = $this->mergedFilter($search, $input);

        $result = $statistics->statistics(
            (string) $request->user()->tenant_id,
            (string) ($input['group_by'] ?? 'clients'),
            (string) ($input['period'] ?? 'today'),
            (string) ($input['marketplace'] ?? 'all'),
            $filter,
        );

        return response()->json($result)->header('Cache-Control', 'private, no-store');
    }

    /** Возвращает запуски выбранного интервала календаря. */
    public function runs(Request $request, BackgroundProcessStatisticsService $statistics, QuerySearchService $search): JsonResponse
    {
        $input = $request->validate([
            'group_by' => ['required', Rule::in(['clients', 'webhooks'])],
            'period' => ['required', Rule::in(['today', 'yesterday', 'week', 'month', 'hours_4', 'hour', 'minutes_15'])],
            'bucket_start' => ['required', 'date'],
            'marketplace' => ['sometimes', Rule::in(['all', 'none', 'wildberries', 'ozon', 'yandex_market'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'filter' => ['sometimes', 'nullable', 'string', 'max:32768'],
            ...$this->searchRules(),
        ]);

        $filter = $this->mergedFilter($search, $input);

        try {
            $result = $statistics->runs(
                (string) $request->user()->tenant_id,
                (string) $input['group_by'],
                (string) $input['period'],
                (string) ($input['marketplace'] ?? 'all'),
                $filter,
                (string) $input['bucket_start'],
                (int) ($input['page'] ?? 1),
            );
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return response()->json($result)->header('Cache-Control', 'private, no-store');
    }

    /** @return array<string, array<int, mixed>> */
    private function searchRules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'search_fields' => ['sometimes', 'array', 'min:1'],
            'search_fields.*' => ['string', Rule::in(['id', 'entity_id', 'created_at', 'status', 'processed_records'])],
            'search_mode' => ['sometimes', Rule::in(['filter', 'highlight'])],
            'search_index' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /** @param array<string, mixed> $input */
    private function mergedFilter(QuerySearchService $search, array $input): ?string
    {
        return $search->merge(
            $input['filter'] ?? null,
            $input['search'] ?? null,
            $input['search_fields'] ?? ['id'],
            ['id', 'entity_id', 'created_at', 'status', 'processed_records'],
            $input['search_mode'] ?? 'filter',
            ['status' => ['queued' => 'В очереди', 'running' => 'Выполняется', 'completed' => 'Завершён', 'failed' => 'Ошибка']],
            ['created_at'],
        );
    }
}
