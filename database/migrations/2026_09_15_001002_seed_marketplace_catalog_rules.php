<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['name' => 'Каталог Wildberries', 'shortname' => 'wildberries_catalog', 'val' => 'WildberriesCatalogRule'],
            ['name' => 'Каталог OZON', 'shortname' => 'ozon_catalog', 'val' => 'OzonCatalogRule'],
        ] as $rule) {
            $typeId = DB::table('integration.type_processing')->where('shortname', 'marketplace_catalog')->value('id');
            if (! $typeId) {
                $typeId = DB::table('integration.type_processing')->insertGetId(['name' => 'Каталог маркетплейса', 'shortname' => 'marketplace_catalog', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('integration.rules')->updateOrInsert(['shortname' => $rule['shortname']], ['name' => $rule['name'], 'type_processing_id' => $typeId, 'val' => $rule['val'], 'status' => 1, 'params' => json_encode([]), 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('integration.rules')->whereIn('shortname', ['wildberries_catalog', 'ozon_catalog'])->delete();
        DB::table('integration.type_processing')->where('shortname', 'marketplace_catalog')->delete();
    }
};
