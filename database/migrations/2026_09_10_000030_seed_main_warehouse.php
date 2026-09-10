<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            if (DB::table('wms.warehouses')->where('name', 'Основной')->whereNull('tenant_id')->exists()) {
                return;
            }
            $typeId = DB::table('wms.type_warehouses')
                ->where('name', 'Универсальный')
                ->whereNull('tenant_id')
                ->value('id');
            if ($typeId === null) {
                throw new RuntimeException('Не найден общий тип склада «Универсальный».');
            }
            DB::table('wms.warehouses')->insert([
                'name' => 'Основной',
                'shortname' => 'main',
                'type_warehouse_id' => $typeId,
                'status' => 1,
                'tenant_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Откат общего склада запрещён: запись может использоваться.');
    }
};
