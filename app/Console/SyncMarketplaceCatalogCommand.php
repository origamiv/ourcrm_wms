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
            SyncMarketplaceCatalogJob::dispatch($webhook->id, $tenant);
        }
        $this->info($this->option('sync') ? 'Синхронизация выполнена.' : 'Синхронизация поставлена в очередь.');

        return self::SUCCESS;
    }
}
