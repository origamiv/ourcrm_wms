<?php

declare(strict_types=1);

namespace App\Mixins;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** @mixin Schema */
final class SchemaMixin
{
    public function createSchema(): callable
    {
        return function (string $name): void {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$name}");
        };
    }

    public function dropSchema(): callable
    {
        return function (string $name): void {
            DB::statement("DROP SCHEMA IF EXISTS {$name} CASCADE");
        };
    }
}
