<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use App\Models\ImportRunStage;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

final class ImportRunStageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 1800;

    public function __construct(public int $importId, public string $stage) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);
        $stage = $import->stages()->where('stage_key', $this->stage)->firstOrFail();
        $stage->forceFill([
            'status' => 'running',
            'started_at' => $stage->started_at ?: now(),
        ])->save();
        $import->forceFill([
            'status' => 'running',
            'current_stage' => $this->stage,
            'started_at' => $import->started_at ?: now(),
            'last_job_id' => $this->job?->getJobId(),
        ])->save();

        $options = [
            '--tenant' => (string) $import->tenant_id,
            '--only' => [$this->stage],
            '--inline' => true,
        ];
        if ($import->source_client_id) {
            $options['--tswms-client-id'] = (string) $import->source_client_id;
        }
        if (($import->options['dry_run'] ?? false) === true) {
            $options['--dry-run'] = true;
        }

        $exitCode = Artisan::call('wms:import:tswms', $options);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: "Этап {$this->stage} завершился с ошибкой.");
        }

        $records = 0;
        if (preg_match('/IMPORT_RECORDS:(\d+)/', Artisan::output(), $match)) {
            $records = (int) $match[1];
        }
        $import->increment('total_records', $records);
        $import->increment('processed_records', $records);
        $import->increment('processed_chunks');
        $import->increment('completed_stages');
        $import->increment('completed_jobs');
        $stage->forceFill([
            'status' => 'completed',
            'total_records' => $records,
            'processed_records' => $records,
            'processed_chunks' => 1,
            'total_chunks' => 1,
            'finished_at' => now(),
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        ImportRunStage::query()
            ->where('import_run_id', $this->importId)
            ->where('stage_key', $this->stage)
            ->update(['status' => 'failed', 'error_message' => $exception->getMessage(), 'finished_at' => now()]);
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId, 'stage:'.$this->stage];
    }
}
