<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $typeId = DB::table('integration.type_processing')->where('shortname', 'marketplace_catalog')->value('id');
        if (! $typeId) {
            $typeId = DB::table('integration.type_processing')->insertGetId([
                'name' => 'Каталог маркетплейса',
                'shortname' => 'marketplace_catalog',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('integration.rules')->updateOrInsert(
            ['shortname' => 'yandex_market_catalog'],
            [
                'name' => 'Каталог Яндекс Маркета',
                'type_processing_id' => $typeId,
                'val' => 'YandexMarketCatalogRule',
                'status' => 1,
                'params' => json_encode([]),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('integration.rules')->where('shortname', 'yandex_market_catalog')->delete();
    }
};
