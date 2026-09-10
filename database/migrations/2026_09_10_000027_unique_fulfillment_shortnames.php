<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'Wildberries FBS')->update(['shortname' => 'wb_fbs', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'OZON FBS')->update(['shortname' => 'oz_fbs', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'Wildberries FBO')->update(['shortname' => 'wb_fbo', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'OZON FBO')->update(['shortname' => 'oz_fbo', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'OZON Real FBS')->update(['shortname' => 'oz_real_fbs', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'YandexMarket FBS')->update(['shortname' => 'ym_fbs', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'YandexMarket DBS')->update(['shortname' => 'ym_dbs', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'OZON FBP')->update(['shortname' => 'oz_fbp', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'Yandex FBP')->update(['shortname' => 'ym_fbp', 'updated_at' => now()]);
        DB::table('wms.delivery_services')->whereNull('tenant_id')->where('name', 'МВидео')->update(['shortname' => 'mvideo', 'updated_at' => now()]);
        DB::statement('CREATE UNIQUE INDEX wms_marketplaces_shortname_unique ON wms.marketplaces (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX wms_delivery_services_shortname_unique ON wms.delivery_services (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS wms_marketplaces_shortname_unique');
        DB::statement('DROP INDEX IF EXISTS wms_delivery_services_shortname_unique');
    }
};
