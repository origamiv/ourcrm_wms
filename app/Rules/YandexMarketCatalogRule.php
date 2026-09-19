<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\IntegrationData;
use App\Models\IntegrationWebhook;
use App\Services\MarketplaceCatalogSyncService;

final class YandexMarketCatalogRule
{
    public function handle(IntegrationData $data, array $params = []): IntegrationData
    {
        return app(MarketplaceCatalogSyncService::class)->sync(IntegrationWebhook::findOrFail($data->webhook_id), $data, 'yandex_market');
    }
}
