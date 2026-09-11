<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $icons = ['receipt', 'putaway', 'picking', 'packing', 'shipping', 'inventory', 'transfer'];
        foreach (DB::table('wms.task_types')->orderBy('id')->get() as $type) {
            $key = strtolower((string) $type->shortname);
            $match = collect($icons)->first(fn (string $icon): bool => str_contains($key, $icon));
            $match ??= $icons[((int) $type->id - 1) % count($icons)] ?? 'receipt';
            DB::table('wms.task_types')->where('id', $type->id)->update(['icon' => '/design/task-types/'.$match.'.svg']);
        }
    }

    public function down(): void {}
};
