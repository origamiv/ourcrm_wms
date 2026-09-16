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

final class ImportRunTaskGoodsChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public int $importId, public int $offset, public int $limit) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);
        $options = [
            '--tenant' => (string) $import->tenant_id,
            '--tswms-client-id' => (string) ($import->source_client_id ?: 0),
            '--only' => ['task_goods'],
            '--inline' => true,
            '--task-goods-offset' => $this->offset,
            '--task-goods-limit' => $this->limit,
        ];
        $exitCode = Artisan::call('wms:import:tswms', $options);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Не удалось импортировать chunk task_goods.');
        }
        $stage = ImportRunStage::query()->where('import_run_id', $import->id)->where('stage_key', 'task_goods')->firstOrFail();
        $records = min($this->limit, max(0, $stage->total_records - $this->offset));
        $import->increment('completed_jobs');
        $import->increment('processed_chunks');
        $import->increment('processed_records', $records);
        $stage->increment('processed_records', $records);
        $stage->increment('processed_chunks');
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId, 'stage:task_goods', 'offset:'.$this->offset];
    }
}
