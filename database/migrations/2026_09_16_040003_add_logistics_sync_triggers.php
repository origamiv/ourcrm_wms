<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ENTITIES = [
        'orders' => ['App\\Models\\Order', ['code','number','client_id','warehouse_id','delivery_service_id','order_status_id','order_source_id','order_cancel_status_id','integration_id','delivery_track','delivery_date','created_date','currency','goods_total_price','goods_count','comment_partner','comment_internal','custom','need_imei','need_uin','need_gtin','need_sgtin','need_expiration','need_gtd','is_b2b','is_crossborder','wb_supply_id','crm_party_id','crm_party_address_id','status','tenant_id','src','created_at','updated_at','deleted_at']],
        'order_goods' => ['App\\Models\\OrderGood', ['order_id','good_id','code','count','price','barcode_from_integration','need_marking','tenant_id','src','created_at','updated_at','deleted_at']],
        'order_histories' => ['App\\Models\\OrderHistory', ['order_id','code','action','good_code','value_old','value_new','event_date','user_id','tenant_id','src','created_at','updated_at','deleted_at']],
        'shipments' => ['App\\Models\\Shipment', ['code','order_id','client_id','warehouse_id','shipment_status_id','created_date','checked_at','sent_at','status','tenant_id','src','created_at','updated_at','deleted_at']],
        'order_statuses' => ['App\\Models\\OrderStatus', ['name','shortname','code','status','tenant_id','src','created_at','updated_at','deleted_at']],
        'order_sources' => ['App\\Models\\OrderSource', ['name','shortname','code','status','tenant_id','src','created_at','updated_at','deleted_at']],
        'order_cancel_statuses' => ['App\\Models\\OrderCancelStatus', ['name','shortname','code','status','tenant_id','src','created_at','updated_at','deleted_at']],
        'logistic_companies' => ['App\\Models\\LogisticCompany', ['name','shortname','code','status','tenant_id','src','created_at','updated_at','deleted_at']],
        'shipment_statuses' => ['App\\Models\\ShipmentStatus', ['name','shortname','code','status','tenant_id','src','created_at','updated_at','deleted_at']],
    ];

    public function up(): void
    {
        foreach (self::ENTITIES as $table => [$entity, $fields]) {
            $trigger = 'wms_'.$table.'_change';
            DB::statement("DROP TRIGGER IF EXISTS {$trigger} ON wms.{$table}");
            $entitySql = str_replace("'", "''", $entity);
            $fieldsSql = str_replace("'", "''", json_encode($fields, JSON_UNESCAPED_UNICODE));
            DB::statement("CREATE TRIGGER {$trigger} AFTER INSERT OR UPDATE OR DELETE ON wms.{$table} FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('{$entitySql}', '{$fieldsSql}')");
            $this->backfill($table, $entity);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::ENTITIES) as $table) {
            DB::statement("DROP TRIGGER IF EXISTS wms_{$table}_change ON wms.{$table}");
        }
    }

    private function backfill(string $table, string $entity): void
    {
        $count = (int) DB::table('wms.'.$table)->whereNotNull('tenant_id')->count();
        if ($count === 0) return;
        $entitySql = str_replace("'", "''", $entity);
        DB::statement("WITH base AS (SELECT tenant_id, revision FROM public.sync_state WHERE tenant_id IS NOT NULL FOR UPDATE), rows AS (SELECT row_number() OVER (PARTITION BY tenant_id ORDER BY id) AS rn, id::text AS entity_id, tenant_id::text AS tenant_id, to_jsonb(t) AS data FROM wms.{$table} t WHERE tenant_id IS NOT NULL) INSERT INTO public.entity_changes (revision, tenant_id, entity, entity_id, operation, data) SELECT base.revision + rows.rn, rows.tenant_id, '{$entitySql}', rows.entity_id, 'upsert', rows.data FROM rows JOIN base ON base.tenant_id::text = rows.tenant_id");
        foreach (DB::table('wms.'.$table)->whereNotNull('tenant_id')->distinct()->pluck('tenant_id') as $tenant) {
            $tenantCount = DB::table('wms.'.$table)->where('tenant_id', $tenant)->count();
            DB::table('public.sync_state')->where('tenant_id', $tenant)->increment('revision', $tenantCount);
        }
    }
};
