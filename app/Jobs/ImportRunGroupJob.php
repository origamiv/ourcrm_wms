<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Throwable;

final class ImportRunGroupJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const GROUPS = [
        ['clients'],
        ['accounts'],
        ['webhooks'],
        ['goods', 'warehouses', 'services', 'task_stages', 'users', 'documents'],
        ['tasks'],
        ['task_goods'],
        ['acceptances'],
        ['cell_goods'],
    ];

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $importId, public int $group) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);
        $stages = self::GROUPS[$this->group] ?? [];
        $selected = $import->options['only'] ?? [];
        if ($selected !== []) {
            $stages = array_values(array_intersect($stages, $selected));
        }
        if ($stages === []) {
            $this->dispatchNextOrFinish($import);

            return;
        }

        if ($this->group === 5 && $stages === ['task_goods']) {
            $import->forceFill(['current_stage' => 'task_goods'])->save();
            ImportRunTaskGoodsCoordinatorJob::dispatch($this->importId, $this->group + 1)
                ->onConnection('redis')->onQueue('imports');

            return;
        }

        $jobs = array_map(fn (string $stage): ImportRunStageJob => new ImportRunStageJob($this->importId, $stage), $stages);
        $import->forceFill([
            'current_stage' => $stages[0],
            'total_jobs' => $import->total_jobs + count($jobs),
            'total_chunks' => $import->total_chunks + count($jobs),
        ])->save();

        $importId = $this->importId;
        $nextGroup = $this->group + 1;
        $batch = Bus::batch($jobs)
            ->name('Import #'.$this->importId.' group '.$this->group)
            ->onConnection('redis')
            ->onQueue('imports')
            ->then(function (Batch $batch) use ($importId, $nextGroup): void {
                $import = ImportRun::query()->find($importId);
                if (! $import) {
                    return;
                }
                if ($nextGroup < count(self::GROUPS)) {
                    self::dispatch($importId, $nextGroup)->onConnection('redis')->onQueue('imports');
                } else {
                    $import->forceFill(['status' => 'completed', 'current_stage' => null, 'finished_at' => now()])->save();
                }
            })
            ->catch(function (Batch $batch, Throwable $exception) use ($importId): void {
                ImportRun::query()->whereKey($importId)->update([
                    'status' => 'failed',
                    'error_class' => $exception::class,
                    'error_message' => $exception->getMessage(),
                    'finished_at' => now(),
                ]);
            })
            ->finally(function (Batch $batch) use ($importId): void {
                ImportRun::query()->whereKey($importId)->update(['batch_id' => $batch->id]);
            });

        $batch->dispatch();
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId, 'group:'.$this->group];
    }

    private function dispatchNextOrFinish(ImportRun $import): void
    {
        $next = $this->group + 1;
        if ($next < count(self::GROUPS)) {
            self::dispatch($this->importId, $next)->onConnection('redis')->onQueue('imports');

            return;
        }
        $import->forceFill(['status' => 'completed', 'current_stage' => null, 'finished_at' => now()])->save();
    }
}
