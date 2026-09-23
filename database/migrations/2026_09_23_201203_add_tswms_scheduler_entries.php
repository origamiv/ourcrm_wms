<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Получаем активные тенанты с проектами TSWMS
        $tswmsTenants = DB::table('public.tenant_projects as tp')
            ->join('public.projects as p', 'p.id', '=', 'tp.project_id')
            ->where('tp.status', 1)
            ->where('p.status', 1)
            ->whereNull('tp.deleted_at')
            ->whereRaw("lower(replace(replace(coalesce(p.name,''), '-', ''), '_', '')) = 'tswms'")
            ->pluck('tp.tenant_id')
            ->unique();

        // Для каждого тенанта создаем расписания по умолчанию
        foreach ($tswmsTenants as $tenantId) {
            $this->createDefaultSchedules($tenantId);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем все расписания TSWMS импорта
        DB::table('public.scheduler')
            ->whereIn('task_key', [
                'tswms_import_references',
                'tswms_import_goods', 
                'tswms_import_orders',
                'tswms_import_tasks',
            ])
            ->delete();
    }

    private function createDefaultSchedules(string $tenantId): void
    {
        $schedules = [
            // Справочники каждые 6 часов, начиная с 9:00
            [
                'task_key' => 'tswms_import_references',
                'name' => 'TSWMS: Справочники (каждые 6 часов)',
                'schedule' => json_encode([
                    'kind' => 'interval',
                    'interval' => 6,
                    'unit' => 'hour',
                    'start_date' => now()->format('Y-m-d'),
                    'time' => '09:00',
                ]),
                'params' => json_encode([
                    'tenant' => $tenantId,
                    'entity-groups' => 'references',
                ]),
            ],
            // Товары раз в день в 9:00
            [
                'task_key' => 'tswms_import_goods',
                'name' => 'TSWMS: Товары (ежедневно в 9:00)',
                'schedule' => json_encode([
                    'kind' => 'interval',
                    'interval' => 1,
                    'unit' => 'day',
                    'start_date' => now()->format('Y-m-d'),
                    'time' => '09:00',
                ]),
                'params' => json_encode([
                    'tenant' => $tenantId,
                    'entity-groups' => 'goods',
                ]),
            ],
            // Заказы каждый час
            [
                'task_key' => 'tswms_import_orders',
                'name' => 'TSWMS: Заказы (каждый час)',
                'schedule' => json_encode([
                    'kind' => 'interval',
                    'interval' => 1,
                    'unit' => 'hour',
                    'start_date' => now()->format('Y-m-d'),
                    'time' => '09:00',
                ]),
                'params' => json_encode([
                    'tenant' => $tenantId,
                    'entity-groups' => 'orders',
                ]),
            ],
            // Задачи каждый час со смещением 30 минут
            [
                'task_key' => 'tswms_import_tasks', 
                'name' => 'TSWMS: Задачи (каждый час)',
                'schedule' => json_encode([
                    'kind' => 'interval',
                    'interval' => 1,
                    'unit' => 'hour',
                    'start_date' => now()->format('Y-m-d'),
                    'time' => '09:30', // Смещение на 30 минут от заказов
                ]),
                'params' => json_encode([
                    'tenant' => $tenantId,
                    'entity-groups' => 'tasks',
                ]),
            ],
        ];

        foreach ($schedules as $schedule) {
            // Проверяем, нет ли уже такого расписания
            $exists = DB::table('public.scheduler')
                ->where('tenant_id', $tenantId)
                ->where('task_key', $schedule['task_key'])
                ->exists();

            if (!$exists) {
                DB::table('public.scheduler')->insert([
                    'tenant_id' => $tenantId,
                    'task_key' => $schedule['task_key'],
                    'name' => $schedule['name'],
                    'schedule' => $schedule['schedule'],
                    'params' => $schedule['params'],
                    'task_type' => 'command',
                    'module' => 'wms',
                    'status' => 1,
                    'next_run_at' => now()->addMinutes(5), // Первый запуск через 5 минут
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
