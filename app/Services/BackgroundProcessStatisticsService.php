<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\ImportRun;
use App\Models\IntegrationWebhook;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class BackgroundProcessStatisticsService
{
    private const PER_PAGE = 50;

    private const RUN_STATUSES = ['completed', 'failed', 'queued', 'running'];

    /**
     * @return array<string, mixed>
     */
    public function statistics(string $tenant, string $groupBy, string $period, int $page): array
    {
        $range = $this->range($period);
        $paginator = $this->groups($tenant, $groupBy, $page);
        $groups = collect($paginator->items());
        $webhooks = $this->webhooksForGroups($tenant, $groupBy, $groups);
        $webhookIds = $webhooks->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

        $summaries = $this->summaries($tenant, $webhookIds, $range['selected_from'], $range['selected_to']);
        $activity = $this->activity($tenant, $webhookIds, $range['statistics_from'], $range['statistics_to'], $range['bucket_unit']);

        return [
            'group_by' => $groupBy,
            'period' => $period,
            'timezone' => (string) config('app.timezone', 'Europe/Moscow'),
            'selected_from' => $range['selected_from']->toIso8601String(),
            'selected_to' => $range['selected_to']->toIso8601String(),
            'statistics_from' => $range['statistics_from']->toIso8601String(),
            'statistics_to' => $range['statistics_to']->toIso8601String(),
            'bucket_unit' => $range['bucket_unit'],
            'data' => $this->rows($groupBy, $groups, $webhooks, $summaries, $activity),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array{selected_from: CarbonImmutable, selected_to: CarbonImmutable, statistics_from: CarbonImmutable, statistics_to: CarbonImmutable, bucket_unit: string}
     */
    private function range(string $period): array
    {
        $now = CarbonImmutable::now((string) config('app.timezone', 'Europe/Moscow'));

        [$from, $to, $statisticsFrom, $unit] = match ($period) {
            'today' => [$now->startOfDay(), $now->startOfDay()->addDay(), $now->startOfDay()->subDays(2), 'hour'],
            'yesterday' => [$now->startOfDay()->subDay(), $now->startOfDay(), $now->startOfDay()->subDays(3), 'hour'],
            'week' => [$now->startOfWeek(CarbonInterface::MONDAY), $now->startOfWeek(CarbonInterface::MONDAY)->addWeek(), $now->startOfWeek(CarbonInterface::MONDAY)->subWeeks(2), 'day'],
            'month' => [$now->startOfMonth(), $now->startOfMonth()->addMonth(), $now->startOfMonth()->subMonthsNoOverflow(2), 'day'],
            'hours_4' => $this->rollingRange($now, 240),
            'hour' => $this->rollingRange($now, 60),
            'minutes_15' => $this->rollingRange($now, 15),
            default => throw new InvalidArgumentException('Неизвестный период статистики.'),
        };

        return [
            'selected_from' => $from,
            'selected_to' => $to,
            'statistics_from' => $statisticsFrom,
            'statistics_to' => $to,
            'bucket_unit' => $unit,
        ];
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable, CarbonImmutable, string}
     */
    private function rollingRange(CarbonImmutable $now, int $minutes): array
    {
        $to = $now->startOfMinute()->addMinute();

        return [$to->subMinutes($minutes), $to, $to->subMinutes($minutes * 3), 'minute'];
    }

    private function groups(string $tenant, string $groupBy, int $page): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($groupBy === 'clients') {
            $webhookClients = $this->activeWebhooks($tenant)
                ->whereNotNull('client_id')
                ->select('client_id');

            return Client::query()
                ->visibleTo($tenant)
                ->whereIn('id', $webhookClients)
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(self::PER_PAGE, ['id', 'name', 'shortname'], 'page', $page);
        }

        return $this->activeWebhooks($tenant)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE, ['id', 'name', 'shortname', 'client_id'], 'page', $page);
    }

    private function activeWebhooks(string $tenant): Builder
    {
        return IntegrationWebhook::query()
            ->visibleTo($tenant)
            ->whereIn('status', [1, 3]);
    }

    private function webhooksForGroups(string $tenant, string $groupBy, Collection $groups): Collection
    {
        if ($groups->isEmpty()) {
            return collect();
        }

        $query = $this->activeWebhooks($tenant)->select(['id', 'client_id']);

        return $groupBy === 'clients'
            ? $query->whereIn('client_id', $groups->pluck('id'))->get()
            : $query->whereIn('id', $groups->pluck('id'))->get();
    }

    private function summaries(string $tenant, array $webhookIds, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        if ($webhookIds === []) {
            return collect();
        }

        return ImportRun::query()
            ->where('tenant_id', $tenant)
            ->whereIn('source_webhook_id', $webhookIds)
            ->whereIn('status', self::RUN_STATUSES)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->selectRaw("source_webhook_id,
                COUNT(*)::integer AS total_runs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END)::integer AS successful_runs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END)::integer AS failed_runs,
                SUM(CASE WHEN status IN ('queued', 'running') THEN 1 ELSE 0 END)::integer AS running_runs")
            ->groupBy('source_webhook_id')
            ->get()
            ->keyBy(static fn (object $row): string => (string) $row->source_webhook_id);
    }

    private function activity(string $tenant, array $webhookIds, CarbonImmutable $from, CarbonImmutable $to, string $unit): Collection
    {
        if ($webhookIds === []) {
            return collect();
        }

        return ImportRun::query()
            ->where('tenant_id', $tenant)
            ->whereIn('source_webhook_id', $webhookIds)
            ->whereIn('status', self::RUN_STATUSES)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->selectRaw("source_webhook_id, date_trunc('{$unit}', created_at) AS bucket, COUNT(*)::integer AS count")
            ->groupBy('source_webhook_id')
            ->groupByRaw("date_trunc('{$unit}', created_at)")
            ->orderBy('bucket')
            ->get()
            ->groupBy(static fn (object $row): string => (string) $row->source_webhook_id);
    }

    private function rows(string $groupBy, Collection $groups, Collection $webhooks, Collection $summaries, Collection $activity): array
    {
        $timezone = (string) config('app.timezone', 'Europe/Moscow');

        return $groups->map(function (object $group) use ($groupBy, $webhooks, $summaries, $activity, $timezone): array {
            $groupWebhooks = $groupBy === 'clients'
                ? $webhooks->where('client_id', $group->id)
                : $webhooks->where('id', $group->id);
            $totals = ['total_runs' => 0, 'successful_runs' => 0, 'failed_runs' => 0, 'running_runs' => 0];
            $withoutRuns = 0;
            $buckets = [];

            foreach ($groupWebhooks as $webhook) {
                $summary = $summaries->get((string) $webhook->id);
                foreach (array_keys($totals) as $key) {
                    $totals[$key] += (int) ($summary?->{$key} ?? 0);
                }
                if ((int) ($summary?->total_runs ?? 0) === 0) {
                    $withoutRuns++;
                }
                foreach ($activity->get((string) $webhook->id, collect()) as $point) {
                    $start = CarbonImmutable::parse((string) $point->bucket, $timezone)->toIso8601String();
                    $buckets[$start] = ($buckets[$start] ?? 0) + (int) $point->count;
                }
            }

            ksort($buckets);

            return [
                'id' => (string) $group->id,
                'name' => (string) ($group->name ?: ($group->shortname ?: ($groupBy === 'clients' ? 'Клиент' : 'Вебхук').' №'.$group->id)),
                ...$totals,
                'without_runs' => $withoutRuns,
                'activity' => collect($buckets)->map(fn (int $count, string $start): array => ['start' => $start, 'count' => $count])->values()->all(),
            ];
        })->values()->all();
    }
}
