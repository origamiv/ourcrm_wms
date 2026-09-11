<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wms.services_ff')->whereIn('id', [1, 4, 5, 11, 12])->update([
            'type_service_ff' => DB::raw("CASE id WHEN 1 THEN 5 WHEN 4 THEN 2 WHEN 5 THEN 2 WHEN 11 THEN 2 WHEN 12 THEN 2 END"),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('wms.services_ff')->whereIn('id', [1, 4, 5, 11, 12])->update([
            'type_service_ff' => DB::raw("CASE id WHEN 1 THEN 4 ELSE 1 END"),
            'updated_at' => now(),
        ]);
    }
};
