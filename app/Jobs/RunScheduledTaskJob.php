<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SchedulerRun;
use App\Services\SchedulerTaskRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class RunScheduledTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $runId) {}

    public function handle(SchedulerTaskRegistry $registry): void
    {
        $run = SchedulerRun::query()->findOrFail($this->runId);
        $run->update(['status' => 'running', 'started_at' => now()]);
        try {
            $task = $registry->find($run->task_key, $run->tenant_id);
            if (! $task) throw new \RuntimeException('Задача больше не зарегистрирована.');
            $params = (array) ($run->scheduler?->params ?? []);
            if ($task['type'] === 'command') {
                $arguments = (array) ($params['arguments'] ?? []);
                $options = (array) ($params['options'] ?? []);
                $commandOptions = [];
                foreach ($options as $key => $value) {
                    if ($value === null || $value === '') continue;
                    $commandOptions[str_starts_with((string) $key, '--') ? (string) $key : '--'.(string) $key] = $value;
                }
                $exit = Artisan::call($task['target'], $arguments + $commandOptions);
                $result = ['exit_code' => $exit, 'output' => mb_substr(Artisan::output(), 0, 10000)];
            } else {
                $arguments = (array) ($params['arguments'] ?? $params);
                Bus::dispatchSync(app()->makeWith($task['target'], $arguments));
                $result = ['dispatched' => true];
            }
            $run->update(['status' => 'completed', 'result' => $result, 'finished_at' => now()]);
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 10000), 'finished_at' => now()]);
            throw $exception;
        }
    }
}
