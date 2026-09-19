<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\SyncMarketplaceCatalogJob;
use App\Models\IntegrationWebhook;
use Illuminate\Console\Command;

final class SyncMarketplaceCatalogCommand extends Command
{
    protected $signature = 'integration:sync-catalog {webhook_id : ID интеграции} {--tenant= : Организация} {--sync : Выполнить синхронно}';

    protected $description = 'Загрузить каталог WB, OZON или Яндекс Маркета и сопоставить его с товарами WMS';

    public function handle(): int
    {
        $webhook = IntegrationWebhook::query()->findOrFail((int) $this->argument('webhook_id'));
        $tenant = (string) ($this->option('tenant') ?: $webhook->tenant_id);
        if ($this->option('sync')) {
            app(SyncMarketplaceCatalogJob::class, ['webhookId' => $webhook->id, 'tenant' => $tenant])->handle();
        } else {
            $webhook->loadMissing('service_obj');
            $service = mb_strtolower((string) ($webhook->service_obj?->shortname ?? $webhook->service_obj?->name));
            $marketplace = match (true) {
                str_contains($service, 'ozon') => 'ozon',
                str_contains($service, 'yandex') && str_contains($service, 'market') => 'yandex_market',
                str_contains($service, 'wildberries') || preg_match('/(^|_)wb($|_)/', $service) === 1 => 'wildberries',
                default => null,
            };
            SyncMarketplaceCatalogJob::dispatch($webhook->id, $tenant)
                ->onConnection('redis')
                ->onQueue(SyncMarketplaceCatalogJob::queueForMarketplace((string) $marketplace));
        }
        $this->info($this->option('sync') ? 'Синхронизация выполнена.' : 'Синхронизация поставлена в очередь.');

        return self::SUCCESS;
    }
}
