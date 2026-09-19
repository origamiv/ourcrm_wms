<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ImportRun;
use App\Models\ImportRunStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class ImportRunService
{
    private const STALE_AFTER_MINUTES = 15;

    public function failStaleRuns(): int
    {
        $updated = 0;

        ImportRun::query()
            ->whereIn('status', ['queued', 'running'])
            ->get()
            ->each(function (ImportRun $import) use (&$updated): void {
                if (! $this->isStale($import)) {
                    return;
                }

                $message = 'Импорт остановлен: обработчик очереди завершил пакет заданий без завершения импорта. Проверьте worker очереди imports.';
                $import->forceFill([
                    'status' => 'failed',
                    'error_class' => RuntimeException::class,
                    'error_message' => $message,
                    'finished_at' => now(),
                ])->save();
                ImportRunStage::query()
                    ->where('import_run_id', $import->id)
                    ->whereIn('status', ['queued', 'running'])
                    ->update([
                        'status' => 'failed',
                        'error_message' => $message,
                        'finished_at' => now(),
                    ]);
                $updated++;
            });

        return $updated;
    }

    private function batchFinished(object $batch): bool
    {
        return $batch->finished_at !== null
            || $batch->cancelled_at !== null
            || ((int) $batch->pending_jobs === 0 && (int) $batch->failed_jobs > 0);
    }

    private function isStale(ImportRun $import): bool
    {
        if ($import->batch_id !== null) {
            if (! Schema::hasTable('job_batches')) {
                return false;
            }
            $batch = DB::table('job_batches')->where('id', $import->batch_id)->first();

            return $batch !== null && $this->batchFinished($batch);
        }

        if (! $import->updated_at || $import->updated_at->greaterThan(now()->subMinutes(self::STALE_AFTER_MINUTES))) {
            return false;
        }

        return ! $this->hasQueuedJob($import->id);
    }

    private function hasQueuedJob(int $importId): bool
    {
        try {
            $redis = Redis::connection();
            $payloads = [
                ...$redis->lrange('queues:imports', 0, -1),
                ...$redis->zrange('queues:imports:reserved', 0, -1),
                ...$redis->zrange('queues:imports:delayed', 0, -1),
            ];
            $patterns = ['importId";i:'.$importId, '"importId":'.$importId];

            foreach ($payloads as $payload) {
                foreach ($patterns as $pattern) {
                    if (str_contains((string) $payload, $pattern)) {
                        return true;
                    }
                }
            }
        } catch (Throwable) {
            return true;
        }

        return false;
    }
}
