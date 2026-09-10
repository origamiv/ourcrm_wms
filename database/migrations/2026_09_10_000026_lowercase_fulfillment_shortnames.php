<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wms.marketplaces')->whereNotNull('shortname')->update([
            'shortname' => DB::raw('lower(shortname)'),
            'updated_at' => now(),
        ]);
        DB::table('wms.delivery_services')->whereNotNull('shortname')->update([
            'shortname' => DB::raw('lower(shortname)'),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException('Откат регистра кратких названий не поддерживается.');
    }
};
