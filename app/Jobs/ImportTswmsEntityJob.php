<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

final class ImportTswmsEntityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 минут на импорт одной сущности

    public function __construct(
        public string $tenantId,
        public string $entity,
        public string $entityGroupName = ''
    ) {}

    public function handle(): void
    {
        $exitCode = Artisan::call('wms:import:tswms', [
            '--tenant' => $this->tenantId,
            '--only' => [$this->entity],
        ]);

        if ($exitCode !== 0) {
            $output = Artisan::output();
            throw new RuntimeException("Импорт сущности {$this->entity} завершился с ошибкой: {$output}");
        }

        logger()->info("Импорт сущности {$this->entity} завершен успешно", [
            'tenant_id' => $this->tenantId,
            'entity' => $this->entity,
            'group' => $this->entityGroupName,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        logger()->error("Импорт сущности {$this->entity} провалился", [
            'tenant_id' => $this->tenantId,
            'entity' => $this->entity,
            'group' => $this->entityGroupName,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function tags(): array
    {
        return [
            'tswms',
            'entity-import',
            'tenant:' . $this->tenantId,
            'entity:' . $this->entity,
            'group:' . $this->entityGroupName,
        ];
    }
}