<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('wms.tasks')->select(['id', 'src'])->orderBy('id')->each(function (object $task): void {
            $src = is_array($task->src) ? $task->src : (json_decode((string) $task->src, true) ?: []);
            $changed = false;
            if (! array_key_exists('goods_count', $src)) {
                $src['goods_count'] = 3;
                $changed = true;
            }
            if (! array_key_exists('pieces_count', $src)) {
                $src['pieces_count'] = 42;
                $changed = true;
            }
            if ($changed) {
                DB::table('wms.tasks')->where('id', $task->id)->update(['src' => json_encode($src, JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void {}
};
