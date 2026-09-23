<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\ImportTswmsScheduledJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ImportTswmsScheduledCommand extends Command
{
    protected $signature = 'wms:import:tswms-scheduled {--tenant= : Тенант WMS} {--entity-groups= : Группы сущностей для импорта (references,goods,orders,tasks)} {--force-time : Игнорировать проверку рабочего времени TSWMS} {--dry-run : Режим проверки без запуска импорта}';

    protected $description = 'Запускает периодический импорт TSWMS с проверкой рабочего времени и группировкой сущностей';

    private const ENTITY_GROUPS = [
        'references' => ['clients', 'accounts', 'webhooks', 'warehouses', 'services', 'task_stages', 'users', 'documents'],
        'goods' => ['goods'],
        'orders' => ['orders', 'order_goods', 'order_histories', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'],
        'tasks' => ['tasks', 'task_goods', 'acceptances', 'cell_goods'],
    ];

    private const TSWMS_WORKING_HOURS = ['start' => 9, 'end' => 23]; // Временно расширяем для демо

    public function handle(): int
    {
        $tenant = (string) ($this->option('tenant') ?: '');
        if ($tenant === '') {
            $this->components->error('Нужно указать --tenant.');
            return self::FAILURE;
        }

        if (!DB::table('public.tenants')->where('id', $tenant)->exists()) {
            $this->components->error('Тенант WMS не найден.');
            return self::FAILURE;
        }

        $entityGroups = $this->parseEntityGroups();
        if (empty($entityGroups)) {
            $this->components->error('Нужно указать корректные группы сущностей: ' . implode(', ', array_keys(self::ENTITY_GROUPS)));
            return self::FAILURE;
        }

        $forceTime = (bool) $this->option('force-time');
        $dryRun = (bool) $this->option('dry-run');

        // Проверка рабочего времени TSWMS
        if (!$forceTime && !$this->isWithinTswmsWorkingHours()) {
            $nextRun = $this->getNextWorkingTime();
            $message = "TSWMS недоступен (рабочее время 9:00-20:00). Следующий запуск в {$nextRun->format('Y-m-d H:i')}.";
            
            if ($dryRun) {
                $this->components->warn($message);
                return self::SUCCESS;
            }

            // Планируем Job на следующее рабочее время
            ImportTswmsScheduledJob::dispatch($tenant, $entityGroups)
                ->delay($nextRun)
                ->onConnection('redis')
                ->onQueue('imports');
                
            $this->components->info($message . ' Job запланирован.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->components->info('Режим проверки: импорт TSWMS будет запущен для групп: ' . implode(', ', $entityGroups));
            $this->components->info('Сущности: ' . $this->getEntitiesDescription($entityGroups));
            return self::SUCCESS;
        }

        // Запускаем импорт через Job
        ImportTswmsScheduledJob::dispatch($tenant, $entityGroups)
            ->onConnection('redis')
            ->onQueue('imports');

        $this->components->info("Запущен периодический импорт TSWMS для тенанта {$tenant}");
        $this->components->info('Группы: ' . implode(', ', $entityGroups));
        $this->line('Отслеживать прогресс можно в Horizon: /horizon или в разделе Импорты.');

        return self::SUCCESS;
    }

    private function parseEntityGroups(): array
    {
        $groups = (string) ($this->option('entity-groups') ?: '');
        if ($groups === '') {
            return [];
        }

        $requestedGroups = array_filter(array_map('trim', explode(',', $groups)));
        $validGroups = [];

        foreach ($requestedGroups as $group) {
            if (array_key_exists($group, self::ENTITY_GROUPS)) {
                $validGroups[] = $group;
            } else {
                $this->components->warn("Неизвестная группа сущностей: {$group}");
            }
        }

        return $validGroups;
    }

    private function isWithinTswmsWorkingHours(): bool
    {
        $now = Carbon::now('Europe/Moscow');
        $hour = $now->hour;
        
        return $hour >= self::TSWMS_WORKING_HOURS['start'] && $hour < self::TSWMS_WORKING_HOURS['end'];
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

    private function getEntitiesDescription(array $groups): string
    {
        $entities = [];
        foreach ($groups as $group) {
            $entities = array_merge($entities, self::ENTITY_GROUPS[$group]);
        }
        
        return implode(', ', $entities);
    }
}