<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TswmsImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class TswmsImportCoordinatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = TswmsImport::query()->findOrFail($this->importId);
        $import->forceFill(['status' => 'running', 'started_at' => now()])->save();
        TswmsImportGroupJob::dispatch($this->importId, 0)->onConnection('redis')->onQueue('tswms-import');
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId];
    }
}
