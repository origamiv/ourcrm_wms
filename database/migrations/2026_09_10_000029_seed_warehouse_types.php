<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $types = [
                ['name' => 'Универсальный', 'shortname' => 'universal'],
                ['name' => 'Фулфилмент-центр', 'shortname' => 'fulfillment_center'],
                ['name' => 'Распределительный центр', 'shortname' => 'distribution_center'],
                ['name' => 'Транзитный склад', 'shortname' => 'transit'],
                ['name' => 'Склад возвратов', 'shortname' => 'returns'],
                ['name' => 'Кросс-докинг', 'shortname' => 'cross_dock'],
                ['name' => 'Даркстор', 'shortname' => 'dark_store'],
                ['name' => 'Склад магазина', 'shortname' => 'store'],
                ['name' => 'Пункт выдачи заказов', 'shortname' => 'pickup_point'],
                ['name' => 'Склад временного хранения', 'shortname' => 'temporary_storage'],
            ];
            foreach ($types as $type) {
                if (DB::table('wms.type_warehouses')->where('name', $type['name'])->exists()) {
                    continue;
                }
                DB::table('wms.type_warehouses')->insert([
                    ...$type,
                    'status' => 1,
                    'tenant_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Откат общих типов складов запрещён: записи могут использоваться.');
    }
};
