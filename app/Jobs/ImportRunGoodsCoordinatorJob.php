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

final class ImportRunGoodsCoordinatorJob implements ShouldQueue
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
            '--tswms-client-id' => (string) ($import->source_client_id ?: 0),
            '--only' => ['goods'],
            '--inline' => true,
            '--goods-count-only' => true,
        ];
        $exitCode = Artisan::call('wms:import:tswms', $options);
        if ($exitCode !== 0 || ! preg_match('/TSWMS_GOODS_COUNT:(\d+)/', Artisan::output(), $match)) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Не удалось определить количество товаров.');
        }
        $count = (int) $match[1];
        $stage = ImportRunStage::query()->where('import_run_id', $import->id)->where('stage_key', 'goods')->firstOrFail();
        $chunks = (int) ceil($count / max(1, $this->limit));
        $stage->forceFill([
            'status' => $count > 0 ? 'running' : 'completed',
            'started_at' => $stage->started_at ?: now(),
            'total_records' => $count,
            'total_chunks' => $chunks,
            'finished_at' => $count > 0 ? null : now(),
        ])->save();
        $import->forceFill([
            'total_records' => $import->total_records + $count,
            'total_chunks' => $import->total_chunks + $chunks,
        ])->save();
        if ($count === 0) {
            $import->increment('completed_stages');
            ImportRunGroupJob::dispatch($this->importId, $this->nextGroup)->onConnection('redis')->onQueue('imports');

            return;
        }
        $import->increment('total_jobs', $chunks);
        ImportRunGoodsChunkJob::dispatch($this->importId, 0, $this->limit, $this->nextGroup)
            ->onConnection('redis')->onQueue('imports');
    }
}
