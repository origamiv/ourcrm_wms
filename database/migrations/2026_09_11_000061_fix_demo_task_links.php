<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $values = [
            1001 => ['status_id' => 3, 'priority_id' => 8, 'progress' => 35],
            1002 => ['status_id' => 2, 'priority_id' => 5, 'progress' => 20],
            1003 => ['status_id' => 1, 'priority_id' => 6, 'progress' => 0],
            1004 => ['status_id' => 3, 'priority_id' => 4, 'progress' => 60],
            1005 => ['status_id' => 2, 'priority_id' => 7, 'progress' => 40],
            1006 => ['status_id' => 4, 'priority_id' => 3, 'progress' => 100],
            1007 => ['status_id' => 5, 'priority_id' => 9, 'progress' => 0],
        ];
        foreach ($values as $id => $value) {
            $row = DB::table('wms.tasks')->where('id', $id)->first(['src']);
            if (! $row) {
                continue;
            }
            $src = is_array($row->src) ? $row->src : (json_decode((string) $row->src, true) ?: []);
            $src['progress'] = $value['progress'];
            DB::table('wms.tasks')->where('id', $id)->update([
                'status_id' => $value['status_id'],
                'priority_id' => $value['priority_id'],
                'src' => json_encode($src, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep the demo tasks usable when this corrective migration is rolled back.
    }
};
