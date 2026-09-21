<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\SyncMarketplaceCatalogJob;
use App\Models\ClientAccount;
use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class SyncMarketplaceCatalogsCommand extends Command
{
    private const RULES = [
        'wildberries' => 'wildberries_catalog',
        'ozon' => 'ozon_catalog',
        'yandex_market' => 'yandex_market_catalog',
    ];

    protected $signature = 'integration:sync-catalogs
                            {tenant : Tenant WMS}
                            {--marketplace= : Фильтр площадки: wildberries, ozon или yandex_market}';

    protected $description = 'Поставить в очередь синхронизацию каталогов маркетплейсов tenant';

    public function handle(): int
    {
        $tenant = (string) $this->argument('tenant');
        $marketplaceFilter = $this->option('marketplace');
        if ($marketplaceFilter !== null && ! array_key_exists((string) $marketplaceFilter, self::RULES)) {
            $this->components->error('Недопустимая площадка. Используйте: wildberries, ozon или yandex_market.');

            return self::INVALID;
        }

        if (! DB::table('public.tenants')->where('id', $tenant)->exists()) {
            $this->components->error("Tenant {$tenant} не найден.");

            return self::FAILURE;
        }

        $rules = IntegrationRule::query()
            ->where('status', 1)
            ->whereIn('shortname', array_values($marketplaceFilter === null
                ? self::RULES
                : [self::RULES[(string) $marketplaceFilter]]))
            ->whereHas('typeProcessing_obj', fn ($query) => $query->where('status', 1)->where('shortname', 'marketplace_catalog'))
            ->get()
            ->keyBy('shortname');

        $queued = 0;
        $duplicates = 0;
        $skipped = 0;
        $invalid = 0;

        IntegrationWebhook::query()
            ->where('tenant_id', $tenant)
            ->where('status', 1)
            ->with(['service_obj', 'client_obj'])
            ->orderBy('id')
            ->each(function (IntegrationWebhook $webhook) use ($tenant, $marketplaceFilter, $rules, &$queued, &$duplicates, &$skipped, &$invalid): void {
                $marketplace = $this->marketplaceFor($webhook);
                if ($marketplace === null || ($marketplaceFilter !== null && $marketplace !== $marketplaceFilter)) {
                    $skipped++;

                    return;
                }

                $rule = $rules->get(self::RULES[$marketplace]);
                $configuredRuleIds = array_map('intval', array_filter((array) $webhook->rules_id));
                if (! $rule || ($configuredRuleIds !== [] && ! in_array((int) $rule->id, $configuredRuleIds, true))) {
                    $skipped++;

                    return;
                }

                if (! $this->credentialsConfigured($webhook, $marketplace, $tenant)) {
                    $invalid++;
                    $skipped++;

                    return;
                }

                $options = [
                    'webhook_id' => (int) $webhook->id,
                    'marketplace' => $marketplace,
                    'account_id' => (int) (($webhook->params ?? [])['account_id'] ?? 0) ?: null,
                    'client_id' => $webhook->client_id,
                    'client_name' => $webhook->client_obj?->name,
                ];
                $accountId = (int) ($options['account_id'] ?? 0) ?: null;

                $activeImport = ImportRun::query()
                    ->where('tenant_id', $tenant)
                    ->where('project', 'marketplace')
                    ->whereIn('status', ['queued', 'running'])
                    ->where(function ($query) use ($webhook, $accountId): void {
                        $query->where('source_webhook_id', $webhook->id);
                        if ($accountId !== null) {
                            $query->orWhere('source_account_id', $accountId);
                        }
                    })
                    ->exists();
                if ($activeImport) {
                    $duplicates++;

                    return;
                }

                try {
                    $import = DB::transaction(function () use ($tenant, $webhook, $marketplace, $accountId, $options): ImportRun {
                        $import = ImportRun::query()->create([
                            'tenant_id' => $tenant,
                            'source_client_id' => $webhook->client_id,
                            'source_webhook_id' => $webhook->id,
                            'source_account_id' => $accountId,
                            'source_system' => $marketplace,
                            'project' => 'marketplace',
                            'name' => $this->importName($marketplace, $webhook->client_obj?->name, $webhook->name),
                            'status' => 'queued',
                            'current_stage' => 'catalog',
                            'total_stages' => 1,
                            'completed_stages' => 0,
                            'options' => $options,
                        ]);
                        ImportRunStage::query()->create([
                            'import_run_id' => $import->id,
                            'stage_number' => 1,
                            'stage_key' => 'catalog',
                            'name' => 'Каталог '.$this->marketplaceName($marketplace),
                            'status' => 'queued',
                        ]);

                        return $import;
                    });
                } catch (UniqueConstraintViolationException) {
                    $duplicates++;

                    return;
                }

                SyncMarketplaceCatalogJob::dispatch($webhook->id, $tenant, $import->id)
                    ->onConnection('redis')
                    ->onQueue(SyncMarketplaceCatalogJob::queueForMarketplace($marketplace));
                $queued++;
            });

        $this->components->info("В очередь поставлено: {$queued}; уже выполняется: {$duplicates}; пропущено: {$skipped}; без обязательных реквизитов: {$invalid}.");

        return self::SUCCESS;
    }

    private function marketplaceFor(IntegrationWebhook $webhook): ?string
    {
        $service = mb_strtolower((string) ($webhook->service_obj?->shortname ?? $webhook->service_obj?->name));

        return match (true) {
            str_contains($service, 'ozon') => 'ozon',
            str_contains($service, 'yandex') && str_contains($service, 'market') => 'yandex_market',
            str_contains($service, 'wildberries') || preg_match('/(^|_)wb($|_)/', $service) === 1 => 'wildberries',
            default => null,
        };
    }

    private function credentialsConfigured(IntegrationWebhook $webhook, string $marketplace, string $tenant): bool
    {
        $accountId = (int) (($webhook->params ?? [])['account_id'] ?? 0);
        $account = $accountId > 0
            ? ClientAccount::query()->where('tenant_id', $tenant)->find($accountId)
            : null;
        if (! $account || (int) $account->status !== 1 || ($webhook->client_id !== null && (int) $account->client_id !== (int) $webhook->client_id)) {
            return false;
        }

        $token = trim((string) $account->token);
        $credentials = (array) ($account->src['credentials'] ?? []);

        return match ($marketplace) {
            'wildberries' => $token !== '',
            'ozon' => $token !== '' && trim((string) ($credentials['key2'] ?? '')) !== '',
            'yandex_market' => $token !== '' && trim((string) ($credentials['business_id'] ?? '')) !== '',
            default => false,
        };
    }

    private function marketplaceName(string $marketplace): string
    {
        return match ($marketplace) {
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            default => 'Яндекс Маркет',
        };
    }

    private function importName(string $marketplace, ?string $clientName, string $webhookName): string
    {
        return 'Каталог '.$this->marketplaceName($marketplace).' — '.($clientName ?: 'Клиент не указан').' — '.$webhookName;
    }
}
