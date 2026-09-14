<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE main.worktime DROP CONSTRAINT IF EXISTS worktime_event_type_check');
        DB::statement("ALTER TABLE main.worktime ADD CONSTRAINT worktime_event_type_check CHECK (event_type IN ('start','pause_start','pause_end','finish','vacation','sick'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE main.worktime DROP CONSTRAINT IF EXISTS worktime_event_type_check');
        DB::statement("ALTER TABLE main.worktime ADD CONSTRAINT worktime_event_type_check CHECK (event_type IN ('start','pause_start','pause_end','finish'))");
    }
};
