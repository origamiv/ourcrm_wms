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
        // Получаем всех активных тенантов
        $tenants = DB::table('public.tenants')->where('status', 1)->pluck('id');
        
        // Определяем шаблоны задач
        $taskTemplates = [
            [
                'name' => 'Импорт TSWMS: Справочники',
                'shortname' => 'tswms_import_references',
                'entity_groups' => 'references',
                'description' => 'Периодический импорт справочников из TSWMS (клиенты, аккаунты, склады, сервисы, пользователи)',
            ],
            [
                'name' => 'Импорт TSWMS: Товары',
                'shortname' => 'tswms_import_goods',
                'entity_groups' => 'goods',
                'description' => 'Ежедневный импорт товаров из TSWMS в рабочее время',
            ],
            [
                'name' => 'Импорт TSWMS: Заказы',
                'shortname' => 'tswms_import_orders',
                'entity_groups' => 'orders',
                'description' => 'Частый импорт заказов и отправлений из TSWMS',
            ],
            [
                'name' => 'Импорт TSWMS: Задачи',
                'shortname' => 'tswms_import_tasks',
                'entity_groups' => 'tasks',
                'description' => 'Частый импорт складских задач и размещений из TSWMS',
            ],
        ];

        // Создаем задачи для каждого тенанта
        foreach ($tenants as $tenantId) {
            foreach ($taskTemplates as $template) {
                // Проверяем, нет ли уже такой задачи у тенанта
                $exists = DB::table('public.scheduler_tasks')
                    ->where('tenant_id', $tenantId)
                    ->where('shortname', $template['shortname'])
                    ->exists();

                if (!$exists) {
                    DB::table('public.scheduler_tasks')->insert([
                        'name' => $template['name'],
                        'shortname' => $template['shortname'],
                        'module' => 'wms',
                        'task_type' => 'command',
                        'target' => 'wms:import:tswms-scheduled',
                        'options' => json_encode([
                            'entity_groups' => $template['entity_groups'],
                            'description' => $template['description'],
                        ]),
                        'status' => 1,
                        'tenant_id' => $tenantId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем добавленные задачи планировщика для всех тенантов
        DB::table('public.scheduler_tasks')
            ->where('target', 'wms:import:tswms-scheduled')
            ->whereIn('shortname', [
                'tswms_import_references',
                'tswms_import_goods',
                'tswms_import_orders',
                'tswms_import_tasks',
            ])
            ->delete();
    }
};
