<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The seed rows are inserted before the change trigger is created.
        // Touch them after the trigger exists so initial synchronization sees them.
        DB::table('wms.type_services')
            ->whereIn('id', [1, 2, 3, 4, 5])
            ->update(['updated_at' => now()]);
    }

    public function down(): void
    {
        // Keep the catalog data intact when rolling back this bookkeeping migration.
    }
};
