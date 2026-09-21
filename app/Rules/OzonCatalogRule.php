<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\IntegrationData;
use App\Models\IntegrationWebhook;
use App\Services\MarketplaceCatalogSyncService;

final class OzonCatalogRule
{
    public function handle(IntegrationData $data, array $params = []): IntegrationData
    {
        return app(MarketplaceCatalogSyncService::class)->sync(
            IntegrationWebhook::findOrFail($data->webhook_id),
            $data,
            'ozon',
            $params['progress'] ?? null,
            isset($params['ozon_last_id']) ? (string) $params['ozon_last_id'] : null,
            (int) ($params['initial_processed'] ?? 0),
            $params['checkpoint'] ?? null,
        );
    }
}
