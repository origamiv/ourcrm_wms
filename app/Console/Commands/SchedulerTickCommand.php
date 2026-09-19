<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RunScheduledTaskJob;
use App\Models\Scheduler;
use App\Services\ImportRunService;
use App\Services\SchedulerScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SchedulerTickCommand extends Command
{
    protected $signature = 'scheduler:tick';

    protected $description = 'Запустить готовые задания планировщика WMS';

    public function handle(SchedulerScheduleService $service, ImportRunService $imports): int
    {
        $stale = $imports->failStaleRuns();
        if ($stale > 0) {
            $this->components->warn("Зависших импортов переведено в ошибку: {$stale}.");
        }
        Scheduler::query()->where('module', 'wms')->where('status', 1)->whereNotNull('next_run_at')->where('next_run_at', '<=', now(SchedulerScheduleService::TIMEZONE))->orderBy('id')->each(function (Scheduler $item) use ($service): void {
            DB::transaction(function () use ($item, $service): void {
                $scheduler = Scheduler::query()->lockForUpdate()->find($item->id);
                if (! $scheduler || $scheduler->status !== 1 || ! $scheduler->next_run_at || $scheduler->next_run_at->isFuture()) {
                    return;
                }
                $run = $scheduler->runs()->create(['tenant_id' => $scheduler->tenant_id, 'module' => $scheduler->module, 'task_key' => $scheduler->task_key, 'status' => 'queued', 'queued_at' => now(SchedulerScheduleService::TIMEZONE)]);
                $scheduler->update(['last_run_at' => $scheduler->next_run_at, 'next_run_at' => $service->next($scheduler->schedule, CarbonImmutable::now(SchedulerScheduleService::TIMEZONE))]);
                RunScheduledTaskJob::dispatch($run->id)->afterCommit();
            });
        });

        return self::SUCCESS;
    }
}
