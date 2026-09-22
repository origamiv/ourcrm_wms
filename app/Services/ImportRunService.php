<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SyncMarketplaceCatalogJob;
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

    /** @var array<int, true>|null */
    private ?array $queuedImportIds = null;

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

                $message = 'Импорт остановлен: процесс внезапно завершился.';
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
        if ($this->queuedImportIds !== null) {
            return isset($this->queuedImportIds[$importId]);
        }

        try {
            $redis = Redis::connection();
            $queueNames = ['imports', ...array_values(SyncMarketplaceCatalogJob::QUEUES)];
            $ids = [];
            foreach ($queueNames as $queueName) {
                $payloads = [
                    ...$redis->lrange('queues:'.$queueName, 0, -1),
                    ...$redis->zrange('queues:'.$queueName.':reserved', 0, -1),
                    ...$redis->zrange('queues:'.$queueName.':delayed', 0, -1),
                ];
                foreach ($payloads as $payload) {
                    $job = json_decode((string) $payload, true);
                    $command = $job['data']['command'] ?? '';
                    if (is_string($command) && preg_match('/"importId";i:(\d+);/', $command, $matches)) {
                        $ids[(int) $matches[1]] = true;
                    } elseif (isset($job['data']['importId']) && is_numeric($job['data']['importId'])) {
                        $ids[(int) $job['data']['importId']] = true;
                    }
                }
            }
            $this->queuedImportIds = $ids;
        } catch (Throwable) {
            return true;
        }

        return isset($this->queuedImportIds[$importId]);
    }
}
