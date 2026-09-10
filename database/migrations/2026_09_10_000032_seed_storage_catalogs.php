<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['name' => 'Ячейки', 'shortname' => 'cells'],
            ['name' => 'Коробки', 'shortname' => 'boxes'],
        ] as $type) {
            DB::table('wms.type_storage')->updateOrInsert(
                ['name' => $type['name'], 'tenant_id' => null],
                [...$type, 'status' => 1, 'tenant_id' => null, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        DB::table('wms.zones')->updateOrInsert(
            ['name' => 'Основная', 'tenant_id' => null],
            ['shortname' => 'main', 'status' => 1, 'tenant_id' => null, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        throw new RuntimeException('Откат общих типов хранения и зоны запрещён: записи могут использоваться.');
    }
};
