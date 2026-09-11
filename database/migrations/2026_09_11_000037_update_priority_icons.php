<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $icons = [
            'blocker' => 'blocker.svg', 'critical' => 'critical.svg', 'major' => 'major.svg',
            'highest' => 'highest.svg', 'high' => 'high.svg', 'medium' => 'medium.svg',
            'low' => 'low.svg', 'lowest' => 'lowest.svg', 'minor' => 'minor.svg', 'trivial' => 'trivial.svg',
        ];
        foreach (DB::table('wms.priorities')->get() as $priority) {
            $key = strtolower((string) ($priority->shortname ?: $priority->name));
            $matched = false;
            foreach (array_keys($icons) as $candidate) {
                if (str_contains($key, $candidate)) {
                    DB::table('wms.priorities')->where('id', $priority->id)->update(['icon' => '/design/priorities/'.$icons[$candidate]]);
                    $matched = true;
                    break;
                }
            }
            if (! $matched && isset(array_values($icons)[$priority->id - 1])) {
                DB::table('wms.priorities')->where('id', $priority->id)->update(['icon' => '/design/priorities/'.array_values($icons)[$priority->id - 1]]);
            }
        }
    }

    public function down(): void {}
};
