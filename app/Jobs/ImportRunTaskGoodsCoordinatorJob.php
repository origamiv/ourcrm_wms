<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use RuntimeException;
use Throwable;

final class ImportRunTaskGoodsCoordinatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $importId, public int $nextGroup, public int $limit = 500) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);
        $options = [
            '--tenant' => (string) $import->tenant_id,
            '--only' => ['task_goods'],
            '--inline' => true,
            '--count-only' => true,
        ];
        if ($import->source_client_id) {
            $options['--tswms-client-id'] = (string) $import->source_client_id;
        }
        $exitCode = Artisan::call('wms:import:tswms', $options);
        if ($exitCode !== 0 || ! preg_match('/TSWMS_TASK_GOODS_COUNT:(\d+)/', Artisan::output(), $match)) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Не удалось определить количество task_goods.');
        }
        $count = (int) $match[1];
        $import->forceFill([
            'total_records' => $import->total_records + $count,
            'total_chunks' => $import->total_chunks + (int) ceil($count / max(1, $this->limit)),
        ])->save();
        $jobs = [];
        for ($offset = 0; $offset < $count; $offset += $this->limit) {
            $jobs[] = new ImportRunTaskGoodsChunkJob($this->importId, $offset, $this->limit);
        }
        if ($jobs === []) {
            $import->increment('completed_stages');
            ImportRunGroupJob::dispatch($this->importId, $this->nextGroup)->onConnection('redis')->onQueue('imports');

            return;
        }
        $import->increment('total_jobs', count($jobs));
        $importId = $this->importId;
        $nextGroup = $this->nextGroup;
        Bus::batch($jobs)
            ->name('Import #'.$importId.' task_goods')
            ->onConnection('redis')
            ->onQueue('imports')
            ->then(function (Batch $batch) use ($importId, $nextGroup): void {
                ImportRun::query()->whereKey($importId)->increment('completed_stages');
                ImportRunGroupJob::dispatch($importId, $nextGroup)->onConnection('redis')->onQueue('imports');
            })
            ->catch(function (Batch $batch, Throwable $exception) use ($importId): void {
                ImportRun::query()->whereKey($importId)->update([
                    'status' => 'failed',
                    'error_class' => $exception::class,
                    'error_message' => $exception->getMessage(),
                    'finished_at' => now(),
                ]);
            })
            ->dispatch();
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId, 'stage:task_goods'];
    }
}
