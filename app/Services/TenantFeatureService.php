<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantSetting;

final class TenantFeatureService
{
    public const AUTO_CREATE_MARKETPLACE_GOODS = 'integration.auto_create_marketplace_goods';

    public function enabled(string $tenant, string $feature): bool
    {
        $value = TenantSetting::query()->where('tenant_id', $tenant)->where('name', $feature)->value('value');

        return is_array($value) && (bool) ($value['enabled'] ?? false);
    }
}
