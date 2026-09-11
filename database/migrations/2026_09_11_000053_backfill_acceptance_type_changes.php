<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed rows were inserted before the change trigger was created.
        // Touch them once so they appear in the shared IndexedDB sync journal.
        DB::table('wms.type_acceptance')->whereIn('id', [1, 2])->update(['updated_at' => now()]);
    }

    public function down(): void {}
};
