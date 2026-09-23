<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\ImportTswmsScheduledJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ImportTswmsScheduledCommand extends Command
{
    protected $signature = 'wms:import:tswms-scheduled {--tenant= : Тенант WMS} {--entity-groups= : Группы сущностей для импорта (references,goods,orders,tasks)} {--with-dependencies : Автоматически добавить зависимые сущности} {--force-time : Игнорировать проверку рабочего времени TSWMS} {--dry-run : Режим проверки без запуска импорта}';

    protected $description = 'Запускает периодический импорт TSWMS с проверкой рабочего времени и группировкой сущностей';

    private const ENTITY_GROUPS = [
        'references' => ['clients', 'services', 'warehouses', 'task_stages', 'users', 'accounts', 'webhooks', 'documents'],
        'goods' => ['goods'],
        'orders' => ['orders', 'order_goods', 'order_histories', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'],
        'tasks' => ['tasks', 'task_goods', 'acceptances', 'cell_goods'],
    ];

    /**
     * Карта зависимостей между сущностями
     */
    private const ENTITY_DEPENDENCIES = [
        'accounts' => ['clients'],
        'webhooks' => ['clients', 'accounts'], 
        'documents' => ['clients'],
        'orders' => ['clients', 'warehouses', 'webhooks', 'services'],
        'order_goods' => ['orders', 'goods'],
        'order_histories' => ['orders'],
        'shipments' => ['orders'],
        'tasks' => ['clients', 'warehouses', 'task_stages', 'users'],
        'task_goods' => ['tasks', 'goods'],
        'acceptances' => ['tasks', 'clients', 'warehouses'],
        'cell_goods' => ['tasks', 'goods', 'warehouses'],
    ];

    private const TSWMS_WORKING_HOURS = ['start' => 9, 'end' => 23]; // Временно расширяем для демо

    public function handle(): int
    {
        $tenant = (string) ($this->option('tenant') ?: '');
        if ($tenant === '') {
            $this->components->error('Нужно указать --tenant.');
            return self::FAILURE;
        }

        $tenantRecord = DB::table('public.tenants')->where('name', $tenant)->first();
        if (!$tenantRecord) {
            $this->components->error('Тенант WMS не найден.');
            return self::FAILURE;
        }
        
        $tenantId = $tenantRecord->id;

        $entityGroups = $this->parseEntityGroups();
        if (empty($entityGroups)) {
            $this->components->error('Нужно указать корректные группы сущностей: ' . implode(', ', array_keys(self::ENTITY_GROUPS)));
            return self::FAILURE;
        }

        $entities = $this->getEntitiesFromGroups($entityGroups);
        
        // Автоматическое добавление зависимостей, если включена опция
        if ($this->option('with-dependencies')) {
            $entities = $this->addDependencies($entities);
            $this->components->info('Импорт будет выполнен для сущностей с зависимостями: ' . implode(', ', $entities));
        } else {
            // Валидация зависимостей
            $this->validateDependencies($entities);
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
                if ($this->option('with-dependencies')) {
                    ImportTswmsScheduledJob::dispatch($tenantId, $entityGroups, $entities)
                        ->delay($nextRun)
                        ->onConnection('redis')
                        ->onQueue('imports');
                } else {
                    ImportTswmsScheduledJob::dispatch($tenantId, $entityGroups)
                        ->delay($nextRun)
                        ->onConnection('redis')
                        ->onQueue('imports');
                }
                
            $this->components->info($message . ' Job запланирован.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->components->info('Режим проверки: импорт TSWMS будет запущен для групп: ' . implode(', ', $entityGroups));
            $this->components->info('Сущности: ' . implode(', ', $entities));
            return self::SUCCESS;
        }

        // Запускаем импорт через Job, передавая конкретные сущности если они были обработаны
        if ($this->option('with-dependencies')) {
            ImportTswmsScheduledJob::dispatch($tenantId, $entityGroups, $entities)
                ->onConnection('redis')
                ->onQueue('imports');
        } else {
            ImportTswmsScheduledJob::dispatch($tenantId, $entityGroups)
                ->onConnection('redis')
                ->onQueue('imports');
        }

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

    /**
     * Добавляет зависимости к списку сущностей
     */
    private function addDependencies(array $entities): array
    {
        $result = $entities;
        $added = [];
        
        foreach ($entities as $entity) {
            $dependencies = self::ENTITY_DEPENDENCIES[$entity] ?? [];
            
            foreach ($dependencies as $dependency) {
                if (!in_array($dependency, $result, true)) {
                    $result[] = $dependency;
                    $added[] = $dependency;
                }
            }
        }
        
        if (!empty($added)) {
            $this->components->info('Автоматически добавлены зависимости: ' . implode(', ', $added));
        }
        
        // Сортируем по порядку из ENTITY_GROUPS для правильной последовательности
        return $this->sortEntitiesByDependencyOrder($result);
    }

    /**
     * Проверяет зависимости для заданных сущностей и выдает предупреждения
     */
    private function validateDependencies(array $entities): void
    {
        $missingDependencies = [];
        
        foreach ($entities as $entity) {
            $dependencies = self::ENTITY_DEPENDENCIES[$entity] ?? [];
            
            foreach ($dependencies as $dependency) {
                if (!in_array($dependency, $entities, true)) {
                    $missingDependencies[$entity][] = $dependency;
                }
            }
        }
        
        if (!empty($missingDependencies)) {
            $this->components->warn('Обнаружены отсутствующие зависимости:');
            
            foreach ($missingDependencies as $entity => $dependencies) {
                $this->components->warn("  • {$entity} требует: " . implode(', ', $dependencies));
            }
            
            $this->components->warn('Некоторые записи могут быть пропущены из-за отсутствующих связанных данных.');
            $this->components->warn('Используйте --with-dependencies для автоматического добавления зависимостей.');
        }
    }

    /**
     * Сортирует сущности в порядке зависимостей для правильного импорта
     */
    private function sortEntitiesByDependencyOrder(array $entities): array
    {
        $allEntities = [];
        foreach (self::ENTITY_GROUPS as $group) {
            $allEntities = array_merge($allEntities, $group);
        }
        
        // Возвращаем сущности в том порядке, в котором они определены в ENTITY_GROUPS
        return array_values(array_intersect($allEntities, $entities));
    }

    /**
     * Получить сущности из групп (переопределяем для использования в новых методах)
     */
    private function getEntitiesFromGroups(array $groups): array
    {
        $entities = [];
        foreach ($groups as $group) {
            if (isset(self::ENTITY_GROUPS[$group])) {
                $entities = array_merge($entities, self::ENTITY_GROUPS[$group]);
            }
        }
        
        return array_unique($entities);
    }
}