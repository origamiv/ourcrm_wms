<?php

declare(strict_types=1);

namespace AppConsole;

use IlluminateConsoleCommand;
use IlluminateSupportFacadesDB;

final class InitializeWmsTenantCommand extends Command
{
    protected $signature = 'wms:initialize {tenant : Идентификатор организации}';

    protected $description = 'Инициализирует WMS для организации: меню, права и контекст синхронизации';

    public function handle(): int
    {
        $tenantId = (string) $this->argument('tenant');
        $tenant = DB::table('public.tenants')->where('id', $tenantId)->first(['id', 'name', 'status', 'owner_user_id']);
        if (! $tenant) {
            $this->components->error("Организация {$tenantId} не найдена.");
            return self::FAILURE;
        }

        DB::transaction(function () use ($tenantId, $tenant): void {
            DB::statement('SELECT wms.initialize_tenant_shares(?)', [$tenantId]);
            if ($tenant->owner_user_id !== null) {
                $adminRole = DB::table('main.roles')->where('slug', 'admin')->where('status', 1)->whereNull('deleted_at')->first('id');
                $assigned = $adminRole && DB::table('main.role_user')->where('role_id', $adminRole->id)->where('user_id', $tenant->owner_user_id)->where('tenant_id', $tenantId)->whereNull('deleted_at')->exists();
                if ($adminRole && ! $assigned) {
                    DB::table('main.role_user')->insert(['role_id' => $adminRole->id, 'user_id' => $tenant->owner_user_id, 'tenant_id' => $tenantId, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });

        $this->call('project:menu');
        $this->call('project:sync_permissions');
        $this->components->info("WMS инициализирован для «{$tenant->name}» ({$tenantId}).");
        $this->line('Проверены контекст синхронизации, общие справочники, меню и права доступа.');
        return self::SUCCESS;
    }
}
