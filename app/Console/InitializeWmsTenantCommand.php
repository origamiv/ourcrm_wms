<?php

declare(strict_types=1);

namespace App\Console;

use App\Services\EntityChangeRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class InitializeWmsTenantCommand extends Command
{
    protected $signature = 'wms:initialize {tenant : Идентификатор организации}';

    protected $description = 'Инициализирует WMS для организации: меню, права и контекст синхронизации';

    public function handle(EntityChangeRecorder $changes): int
    {
        $tenantId = (string) $this->argument('tenant');
        $tenant = DB::table('public.tenants')->where('id', $tenantId)->first(['id', 'name', 'status', 'owner_user_id']);
        if (! $tenant) {
            $this->components->error("Организация {$tenantId} не найдена.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($tenantId, $tenant, $changes): void {
            $changes->initializeTenantShares($tenantId);
            $this->copyRoles($tenantId);
            $this->copyFulfillmentCatalogs($tenantId);
            if ($tenant->owner_user_id !== null) {
                $adminRole = DB::table('main.roles')->where('slug', 'admin')->where('status', 1)->whereNull('deleted_at')->first('id');
                // В legacy-схеме уникальность задана по user_id + role_id, поэтому
                // не создаём вторую строку, если роль уже назначена владельцу.
                $assigned = $adminRole && DB::table('main.role_user')->where('role_id', $adminRole->id)->where('user_id', $tenant->owner_user_id)->whereNull('deleted_at')->exists();
                if ($adminRole && ! $assigned) {
                    DB::table('main.role_user')->insert(['role_id' => $adminRole->id, 'user_id' => $tenant->owner_user_id, 'tenant_id' => $tenantId, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            $this->publishCopiedEntities($changes, $tenantId);
            if ($tenant->owner_user_id !== null && ! $changes->hasDatabaseTrigger('main.role_user')) {
                $changes->publishCurrent(\App\Models\User::class, $tenant->owner_user_id);
            }
        });

        $this->call('project:menu');
        $this->call('project:sync_permissions');
        $this->components->info("WMS инициализирован для «{$tenant->name}» ({$tenantId}).");
        $this->line('Проверены контекст синхронизации, общие справочники, меню и права доступа.');

        return self::SUCCESS;
    }

    private function copyRoles(string $tenantId): void
    {
        $template = '29b164bf-3043-42a4-8de7-705ff3e502c7';
        $this->syncSequence('main.roles');
        $this->syncSequence('main.permission_role');
        $roleMap = [];
        foreach (DB::table('main.roles')->where('tenant_id', $template)->whereNull('deleted_at')->get() as $role) {
            $values = (array) $role;
            unset($values['id']);
            $values['tenant_id'] = $tenantId;
            $values['created_at'] = now();
            $values['updated_at'] = now();
            DB::table('main.roles')->updateOrInsert(['tenant_id' => $tenantId, 'slug' => $role->slug], $values);
            $target = DB::table('main.roles')->where('tenant_id', $tenantId)->where('slug', $role->slug)->first('id');
            $roleMap[(string) $role->id] = $target->id;
        }
        foreach (DB::table('main.permission_role')->where('tenant_id', $template)->whereNull('deleted_at')->get() as $link) {
            if (! isset($roleMap[(string) $link->role_id])) {
                continue;
            }
            $values = (array) $link;
            unset($values['id']);
            $values['tenant_id'] = $tenantId;
            $values['role_id'] = $roleMap[(string) $link->role_id];
            $values['created_at'] = now();
            $values['updated_at'] = now();
            DB::table('main.permission_role')->updateOrInsert(['tenant_id' => $tenantId, 'role_id' => $values['role_id'], 'permission_id' => $link->permission_id], $values);
        }
    }

    private function copyFulfillmentCatalogs(string $tenantId): void
    {
        $template = '29b164bf-3043-42a4-8de7-705ff3e502c7';
        $now = now();
        foreach (['wms.marketplaces', 'wms.type_services', 'wms.services_ff', 'wms.delivery_services', 'wms.warehouses', 'wms.zones'] as $table) {
            $this->syncSequence($table);
        }
        foreach (['wms.marketplaces', 'wms.type_services', 'wms.services_ff'] as $table) {
            $rows = DB::table($table)->where('tenant_id', $template)->whereNull('deleted_at')->get();
            foreach ($rows as $row) {
                $values = (array) $row;
                unset($values['id']);
                $values['tenant_id'] = $tenantId;
                $values['shortname'] = $this->tenantShortname($table, (string) $row->shortname, $tenantId);
                $values['created_at'] = $now;
                $values['updated_at'] = $now;
                DB::table($table)->updateOrInsert(['tenant_id' => $tenantId, 'shortname' => $values['shortname']], $values);
            }
        }
        $marketplaceMap = [];
        foreach (DB::table('wms.marketplaces')->where('tenant_id', $template)->whereNull('deleted_at')->get() as $row) {
            $target = DB::table('wms.marketplaces')->where('tenant_id', $tenantId)->where('name', $row->name)->first('id');
            if ($target) {
                $marketplaceMap[(string) $row->id] = $target->id;
            }
        }
        foreach (DB::table('wms.delivery_services')->where('tenant_id', $template)->whereNull('deleted_at')->get() as $row) {
            $values = (array) $row;
            unset($values['id']);
            $values['tenant_id'] = $tenantId;
            $values['shortname'] = $this->tenantShortname('wms.delivery_services', (string) $row->shortname, $tenantId);
            $values['marketplace_id'] = $row->marketplace_id ? ($marketplaceMap[(string) $row->marketplace_id] ?? null) : null;
            $values['created_at'] = $now;
            $values['updated_at'] = $now;
            DB::table('wms.delivery_services')->updateOrInsert(['tenant_id' => $tenantId, 'shortname' => $values['shortname']], $values);
        }
        $type = DB::table('wms.type_warehouses')->whereNull('deleted_at')->orderBy('id')->first();
        DB::table('wms.warehouses')->updateOrInsert(['tenant_id' => $tenantId, 'name' => 'Основной'], ['name' => 'Основной', 'shortname' => $this->tenantShortname('wms.warehouses', 'main', $tenantId), 'type_warehouse_id' => $type?->id, 'status' => 1, 'updated_at' => $now, 'created_at' => $now]);
        DB::table('wms.zones')->updateOrInsert(['tenant_id' => $tenantId, 'name' => 'Основная'], ['name' => 'Основная', 'shortname' => $this->tenantShortname('wms.zones', 'main', $tenantId), 'status' => 1, 'updated_at' => $now, 'created_at' => $now]);
    }

    private function tenantShortname(string $table, string $shortname, string $tenantId): string
    {
        $candidate = $shortname !== '' ? $shortname : 'item';
        if (! DB::table($table)->where('shortname', $candidate)->whereNull('deleted_at')->exists()) {
            return $candidate;
        }

        return $candidate.'_'.mb_substr(str_replace('-', '', $tenantId), 0, 8);
    }

    private function syncSequence(string $table): void
    {
        [$schema, $name] = explode('.', $table, 2);
        $sequence = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS sequence', [$schema.'.'.$name, 'id'])->sequence ?? null;
        if ($sequence) {
            DB::statement("SELECT setval(?, COALESCE((SELECT MAX(id) FROM {$schema}.{$name}), 1), true)", [$sequence]);
        }
    }

    private function publishCopiedEntities(EntityChangeRecorder $changes, string $tenant): void
    {
        foreach ($changes->definitions() as $entity => $definition) {
            if ($changes->hasDatabaseTrigger($definition['table']) || ! in_array($definition['table'], [
                'main.roles', 'main.permission_role', 'wms.marketplaces', 'wms.type_services',
                'wms.services_ff', 'wms.delivery_services', 'wms.warehouses', 'wms.zones',
            ], true)) {
                continue;
            }
            $ids = DB::table($definition['table'])->where('tenant_id', $tenant)->pluck('id');
            $changes->publishMany($entity, $ids);
        }
    }
}
