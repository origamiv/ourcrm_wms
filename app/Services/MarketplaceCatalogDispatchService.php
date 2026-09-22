<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SyncMarketplaceCatalogJob;
use App\Models\ClientAccount;
use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Models\IntegrationWebhook;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class MarketplaceCatalogDispatchService
{
    public function dispatch(IntegrationWebhook $webhook, string $marketplace, bool $enqueue = true): ImportRun
    {
        return DB::transaction(function () use ($webhook, $marketplace, $enqueue): ImportRun {
            $account = ClientAccount::query()->where('tenant_id', $webhook->tenant_id)
                ->lockForUpdate()->findOrFail((int) ($webhook->params['account_id'] ?? 0));
            if ((int) $account->status !== 1 || ($webhook->client_id !== null && (int) $account->client_id !== (int) $webhook->client_id)) {
                throw new InvalidArgumentException('Аккаунт отключён или не принадлежит клиенту интеграции.');
            }
            $existing = ImportRun::query()->where('tenant_id', $webhook->tenant_id)->where('project', 'marketplace')
                ->whereIn('status', ['queued', 'running'])
                ->where(fn ($query) => $query->where('source_webhook_id', $webhook->id)->orWhere('source_account_id', $account->id))->first();
            if ($existing) {
                return $existing;
            }
            $run = ImportRun::query()->create([
                'tenant_id' => $webhook->tenant_id, 'source_client_id' => $webhook->client_id,
                'source_webhook_id' => $webhook->id, 'source_account_id' => $account->id,
                'source_system' => $marketplace, 'project' => 'marketplace',
                'name' => 'Каталог '.$marketplace.' — '.$webhook->name,
                'status' => 'queued', 'current_stage' => 'catalog', 'total_stages' => 1,
                'options' => ['webhook_id' => $webhook->id, 'account_id' => $account->id, 'marketplace' => $marketplace],
            ]);
            ImportRunStage::query()->create([
                'import_run_id' => $run->id, 'stage_number' => 1, 'stage_key' => 'catalog',
                'name' => 'Каталог '.$marketplace, 'status' => 'queued',
            ]);
            if ($enqueue) {
                SyncMarketplaceCatalogJob::dispatch((int) $webhook->id, (string) $webhook->tenant_id, (int) $run->id)
                    ->onConnection('redis')->onQueue(SyncMarketplaceCatalogJob::queueForMarketplace($marketplace))->afterCommit();
            }
            $webhook->forceFill(['dat_last_run' => now()])->save();

            return $run;
        });
    }
}
