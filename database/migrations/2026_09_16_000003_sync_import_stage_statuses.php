<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('wms.import_runs')->where('status', 'failed')->whereNotNull('current_stage')->get(['id', 'current_stage']) as $run) {
            DB::table('wms.import_run_stages')
                ->where('import_run_id', $run->id)
                ->where('stage_key', $run->current_stage)
                ->update(['status' => 'failed', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Состояния этапов являются текущим журналом выполнения и не откатываются.
    }
};
