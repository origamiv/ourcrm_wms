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
use RuntimeException;

final class ImportRunCoordinatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; // Увеличиваем количество попыток для обработки ошибок подключения
    public int $timeout = 600; // 10 минут для проверки доступности и запуска

    private const TSWMS_WORKING_HOURS = ['start' => 9, 'end' => 20];

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = ImportRun::query()->findOrFail($this->importId);

        // Проверяем рабочее время TSWMS
        if (!$this->isWithinTswmsWorkingHours()) {
            $this->rescheduleForWorkingHours($import);
            return;
        }

        // Проверяем доступность TSWMS перед запуском
        if (!$this->checkTswmsAvailability($import)) {
            $this->rescheduleWithDelay($import, 30);
            return;
        }

        $import->forceFill(['status' => 'running', 'started_at' => now()])->save();
        ImportRunGroupJob::dispatch($this->importId, 0)->onConnection('redis')->onQueue('imports');
    }

    public function failed(\Throwable $exception): void
    {
        $import = ImportRun::query()->find($this->importId);
        if ($import) {
            $import->forceFill([
                'status' => 'failed',
                'error_class' => $exception::class,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ])->save();
        }

        logger()->error('ImportRunCoordinatorJob failed', [
            'import_id' => $this->importId,
            'exception' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['tswms', 'import:'.$this->importId];
    }

    private function isWithinTswmsWorkingHours(): bool
    {
        $now = Carbon::now('Europe/Moscow');
        $hour = $now->hour;
        
        return $hour >= self::TSWMS_WORKING_HOURS['start'] && $hour < self::TSWMS_WORKING_HOURS['end'];
    }

    private function checkTswmsAvailability(ImportRun $import): bool
    {
        try {
            // Быстрая проверка доступности TSWMS через сухой запуск команды
            $exitCode = Artisan::call('wms:import:tswms', [
                '--tenant' => $import->tenant_id,
                '--tswms-client-id' => $import->source_client_id,
                '--dry-run' => true,
                '--only' => ['clients'], // Минимальная проверка
            ]);

            return $exitCode === 0;
        } catch (\Throwable $exception) {
            logger()->warning('TSWMS availability check failed', [
                'import_id' => $this->importId,
                'tenant_id' => $import->tenant_id,
                'exception' => $exception->getMessage(),
            ]);
            
            return false;
        }
    }

    private function rescheduleForWorkingHours(ImportRun $import): void
    {
        $nextWorkingTime = $this->getNextWorkingTime();
        
        $import->forceFill([
            'status' => 'queued',
            'error_message' => "Отложен до рабочего времени TSWMS. Следующий запуск: {$nextWorkingTime->format('Y-m-d H:i')}",
        ])->save();

        logger()->info('TSWMS import rescheduled for working hours', [
            'import_id' => $this->importId,
            'tenant_id' => $import->tenant_id,
            'next_run' => $nextWorkingTime->toDateTimeString(),
        ]);

        self::dispatch($this->importId)
            ->delay($nextWorkingTime)
            ->onConnection('redis')
            ->onQueue('imports');
    }

    private function rescheduleWithDelay(ImportRun $import, int $minutes): void
    {
        $delay = now()->addMinutes($minutes);
        
        // Если задержка выходит за рабочее время, переносим на следующий рабочий день
        if (!$this->isTimeWithinWorkingHours($delay)) {
            $this->rescheduleForWorkingHours($import);
            return;
        }

        $import->forceFill([
            'status' => 'queued',
            'error_message' => "TSWMS недоступен. Повтор через {$minutes} минут в {$delay->format('H:i')}",
        ])->save();

        logger()->info('TSWMS import rescheduled with delay', [
            'import_id' => $this->importId,
            'tenant_id' => $import->tenant_id,
            'delay_minutes' => $minutes,
            'next_run' => $delay->toDateTimeString(),
        ]);

        self::dispatch($this->importId)
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
}
