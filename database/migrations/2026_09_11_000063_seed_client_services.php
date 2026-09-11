<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE clients.services ALTER COLUMN client_id DROP NOT NULL');
        DB::statement('ALTER TABLE clients.services ALTER COLUMN tenant_id DROP NOT NULL');

        foreach ([
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            'yandex_market' => 'Yandex.Market',
        ] as $shortname => $name) {
            if (DB::table('clients.services')->where('shortname', $shortname)->whereNull('deleted_at')->exists()) {
                continue;
            }

            DB::table('clients.services')->insert([
                'name' => $name,
                'shortname' => $shortname,
                'status' => 1,
                'client_id' => null,
                'tenant_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('clients.services')
            ->whereIn('shortname', ['wildberries', 'ozon', 'yandex_market'])
            ->whereNull('client_id')
            ->whereNull('tenant_id')
            ->delete();
    }
};
