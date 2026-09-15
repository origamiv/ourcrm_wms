<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('public.tenant_projects')->whereNotNull('options')->get(['id', 'options']) as $row) {
            $options = is_string($row->options) ? json_decode($row->options, true) : (array) $row->options;
            if (! is_array($options) || isset($options['client'])) continue;
            foreach ($options as $key => $value) {
                if (preg_match('/^client(?:_|-)\d+$/i', (string) $key)) {
                    $options['client'] = $value;
                    unset($options[$key]);
                    DB::table('public.tenant_projects')->where('id', $row->id)->update(['options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        // The original client key contains a source ID that is not recoverable safely.
    }
};
