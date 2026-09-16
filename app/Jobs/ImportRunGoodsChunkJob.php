<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use App\Models\ImportRunStage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

final class ImportRunGoodsChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public int $importId, public int $offset, public int $limit, public int $nextGroup) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);
        $exitCode = Artisan::call('wms:import:tswms', [
            '--tenant' => (string) $import->tenant_id,
            '--tswms-client-id' => (string) ($import->source_client_id ?: 0),
            '--only' => ['goods'],
            '--inline' => true,
            '--goods-offset' => $this->offset,
            '--goods-limit' => $this->limit,
        ]);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Не удалось импортировать chunk товаров.');
        }
        $stage = ImportRunStage::query()->where('import_run_id', $import->id)->where('stage_key', 'goods')->firstOrFail();
        $records = min($this->limit, max(0, $stage->total_records - $this->offset));
        $import->increment('completed_jobs');
        $import->increment('processed_chunks');
        $import->increment('processed_records', $records);
        $stage->increment('processed_records', $records);
        $stage->increment('processed_chunks');
        if ($this->offset + $this->limit < $stage->total_records) {
            self::dispatch($this->importId, $this->offset + $this->limit, $this->limit, $this->nextGroup)
                ->onConnection('redis')->onQueue('imports');

            return;
        }
        ImportRun::query()->whereKey($this->importId)->increment('completed_stages');
        $stage->forceFill(['status' => 'completed', 'finished_at' => now()])->save();
        ImportRunGroupJob::dispatch($this->importId, $this->nextGroup)->onConnection('redis')->onQueue('imports');
    }

    public function failed(Throwable $exception): void
    {
        ImportRunStage::query()->where('import_run_id', $this->importId)->where('stage_key', 'goods')->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
        ImportRun::query()->whereKey($this->importId)->update([
            'status' => 'failed',
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
