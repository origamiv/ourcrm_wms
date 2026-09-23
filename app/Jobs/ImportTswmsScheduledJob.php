<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class ImportTswmsScheduledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 минут для проверки и запуска

    private const ENTITY_GROUPS = [
        'references' => ['clients', 'accounts', 'webhooks', 'warehouses', 'services', 'task_stages', 'users', 'documents'],
        'goods' => ['goods'],
        'orders' => ['orders', 'order_goods', 'order_histories', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'],
        'tasks' => ['tasks', 'task_goods', 'acceptances', 'cell_goods'],
    ];

    private const TSWMS_WORKING_HOURS = ['start' => 9, 'end' => 23]; // Временно расширяем для демо

    public function __construct(
        public string $tenantId,
        public array $entityGroups
    ) {}

    public function handle(): void
    {
        // Проверяем рабочее время TSWMS перед запуском
        if (!$this->isWithinTswmsWorkingHours()) {
            $this->rescheduleForWorkingHours();
            return;
        }

        // Проверяем, есть ли активные импорты для этого тенанта
        if ($this->hasActiveImport()) {
            $this->rescheduleWithDelay(30); // Retry через 30 минут
            return;
        }

        try {
            $this->runImport();
        } catch (Throwable $exception) {
            $this->handleImportError($exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        // Логируем ошибку для мониторинга
        logger()->error('TSWMS scheduled import failed', [
            'tenant_id' => $this->tenantId,
            'entity_groups' => $this->entityGroups,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function tags(): array
    {
        return [
            'tswms',
            'scheduled-import',
            'tenant:' . $this->tenantId,
            ...array_map(fn($group) => 'group:' . $group, $this->entityGroups)
        ];
    }

    private function isWithinTswmsWorkingHours(): bool
    {
        $now = Carbon::now('Europe/Moscow');
        $hour = $now->hour;
        
        return $hour >= self::TSWMS_WORKING_HOURS['start'] && $hour < self::TSWMS_WORKING_HOURS['end'];
    }

    private function hasActiveImport(): bool
    {
        return ImportRun::query()
            ->where('tenant_id', $this->tenantId)
            ->whereIn('status', ['queued', 'running'])
            ->exists();
    }

    private function runImport(): void
    {
        $entities = $this->getEntitiesFromGroups();
        
        if (empty($entities)) {
            throw new RuntimeException('Не найдены сущности для импорта в группах: ' . implode(', ', $this->entityGroups));
        }

        // Проверяем доступность TSWMS перед запуском
        if (!$this->checkTswmsAvailability()) {
            $this->rescheduleWithDelay(30);
            return;
        }

        // Запускаем отдельный импорт для каждой сущности
        foreach ($entities as $entity) {
            $exitCode = Artisan::call('wms:import:tswms', [
                '--tenant' => $this->tenantId,
                '--only' => [$entity],
            ]);

            if ($exitCode !== 0) {
                $output = Artisan::output();
                logger()->warning("Импорт сущности {$entity} завершился с ошибкой", [
                    'tenant_id' => $this->tenantId,
                    'entity' => $entity,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);
                // Продолжаем с другими сущностями даже если одна упала
            }
        }
    }

    private function checkTswmsAvailability(): bool
    {
        try {
            // Быстрая проверка доступности TSWMS через сухой запуск
            $exitCode = Artisan::call('wms:import:tswms', [
                '--tenant' => $this->tenantId,
                '--dry-run' => true,
                '--only' => ['clients'], // Минимальная проверка
            ]);

            return $exitCode === 0;
        } catch (Throwable $exception) {
            logger()->warning('TSWMS availability check failed', [
                'tenant_id' => $this->tenantId,
                'exception' => $exception->getMessage(),
            ]);
            
            return false;
        }
    }

    private function rescheduleForWorkingHours(): void
    {
        $nextWorkingTime = $this->getNextWorkingTime();
        
        logger()->info('TSWMS import rescheduled for working hours', [
            'tenant_id' => $this->tenantId,
            'entity_groups' => $this->entityGroups,
            'next_run' => $nextWorkingTime->toDateTimeString(),
        ]);

        self::dispatch($this->tenantId, $this->entityGroups)
            ->delay($nextWorkingTime)
            ->onConnection('redis')
            ->onQueue('imports');
    }

    private function rescheduleWithDelay(int $minutes): void
    {
        $delay = now()->addMinutes($minutes);
        
        // Если задержка выходит за рабочее время, переносим на следующий рабочий день
        if (!$this->isTimeWithinWorkingHours($delay)) {
            $this->rescheduleForWorkingHours();
            return;
        }

        logger()->info('TSWMS import rescheduled with delay', [
            'tenant_id' => $this->tenantId,
            'entity_groups' => $this->entityGroups,
            'delay_minutes' => $minutes,
            'next_run' => $delay->toDateTimeString(),
        ]);

        self::dispatch($this->tenantId, $this->entityGroups)
            ->delay($delay)
            ->onConnection('redis')
            ->onQueue('imports');
    }

    private function getNextWorkingTime(): Carbon
    {
        $now = Carbon::now('Europe/Moscow');
        $startHour = self::TSWMS_WORKING_HOURS['start'];

        // Если сейчас до 9 утра, запускаем сегодня в 9:00
        if ($now->hour < $startHour) {
            return $now->setTime($startHour, 0, 0);
        }

        // Если после 20:00, запускаем завтра в 9:00
        return $now->addDay()->setTime($startHour, 0, 0);
    }

    private function isTimeWithinWorkingHours(Carbon $time): bool
    {
        $moscowTime = $time->setTimezone('Europe/Moscow');
        $hour = $moscowTime->hour;
        
        return $hour >= self::TSWMS_WORKING_HOURS['start'] && $hour < self::TSWMS_WORKING_HOURS['end'];
    }

    private function getEntitiesFromGroups(): array
    {
        $entities = [];
        foreach ($this->entityGroups as $group) {
            if (isset(self::ENTITY_GROUPS[$group])) {
                $entities = array_merge($entities, self::ENTITY_GROUPS[$group]);
            }
        }
        
        return array_unique($entities);
    }

    private function handleImportError(Throwable $exception): void
    {
        $isConnectionError = $this->isConnectionError($exception);
        
        if ($isConnectionError && $this->isWithinTswmsWorkingHours()) {
            // Если это ошибка подключения в рабочее время, попробуем позже
            $this->rescheduleWithDelay(30);
            return;
        }

        if ($isConnectionError) {
            // Если ошибка подключения вне рабочего времени, переносим на утро
            $this->rescheduleForWorkingHours();
            return;
        }

        // Для других ошибок - пробрасываем наверх
        throw $exception;
    }

    private function isConnectionError(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());
        
        return str_contains($message, 'connection') ||
               str_contains($message, 'timeout') ||
               str_contains($message, 'refused') ||
               str_contains($message, 'подключ') ||
               str_contains($message, 'billing') ||
               str_contains($message, 'mysql');
    }
}