<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ImportRunCoordinatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);
        $import->forceFill(['status' => 'running', 'started_at' => now()])->save();
        ImportRunGroupJob::dispatch($this->importId, 0)->onConnection('redis')->onQueue('imports');
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId];
    }
}
