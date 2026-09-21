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
use Throwable;

final class SyncMarketplaceCatalogsCommand extends Command
{
    private const MAX_SYNCHRONOUS_ATTEMPTS = 5;

    private const RULES = [
        'wildberries' => 'wildberries_catalog',
        'ozon' => 'ozon_catalog',
        'yandex_market' => 'yandex_market_catalog',
    ];

    protected $signature = 'integration:sync-catalogs
                            {tenant : Tenant WMS}
                            {--marketplace= : Фильтр площадки: wildberries, ozon или yandex_market}
                            {--limit= : Максимальное количество новых запусков}
                            {--sync : Выполнять запуски последовательно, без очереди}';

    protected $description = 'Запустить синхронизацию каталогов маркетплейсов tenant';

    public function handle(): int
    {
        $tenant = (string) $this->argument('tenant');
        $marketplaceFilter = $this->option('marketplace');
        $limitOption = $this->option('limit');
        $synchronous = (bool) $this->option('sync');
        $limit = $limitOption === null ? null : (int) $limitOption;
        if ($limitOption !== null && $limit < 1) {
            $this->components->error('Параметр --limit должен быть положительным числом.');

            return self::INVALID;
        }
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
            ->each(function (IntegrationWebhook $webhook) use ($tenant, $marketplaceFilter, $limit, $synchronous, $rules, &$queued, &$duplicates, &$skipped, &$invalid): ?bool {
                if ($limit !== null && $queued >= $limit) {
                    return false;
                }
                $marketplace = $this->marketplaceFor($webhook);
                if ($marketplace === null || ($marketplaceFilter !== null && $marketplace !== $marketplaceFilter)) {
                    $skipped++;

                    return null;
                }

                $rule = $rules->get(self::RULES[$marketplace]);
                $configuredRuleIds = array_map('intval', array_filter((array) $webhook->rules_id));
                if (! $rule || ($configuredRuleIds !== [] && ! in_array((int) $rule->id, $configuredRuleIds, true))) {
                    $skipped++;

                    return null;
                }

                if (! $this->credentialsConfigured($webhook, $marketplace, $tenant)) {
                    $invalid++;
                    $skipped++;

                    return null;
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

                    return null;
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

                    return null;
                }

                if ($synchronous) {
                    try {
                        $this->runSynchronously($webhook->id, $tenant, $import);
                        $this->components->info("Импорт #{$import->id} завершён: ".(string) $import->fresh()?->status.'.');
                    } catch (Throwable $exception) {
                        $this->markImportFailed($import, $exception);
                        $this->components->warn("Импорт #{$import->id} завершён с ошибкой: ".trim($exception->getMessage()));
                    }
                } else {
                    SyncMarketplaceCatalogJob::dispatch($webhook->id, $tenant, $import->id)
                        ->onConnection('redis')
                        ->onQueue(SyncMarketplaceCatalogJob::queueForMarketplace($marketplace));
                }
                $queued++;

                return null;
            });

        $mode = $synchronous ? 'Последовательно выполнено' : 'В очередь поставлено';
        $this->components->info("{$mode}: {$queued}; уже выполняется: {$duplicates}; пропущено: {$skipped}; без обязательных реквизитов: {$invalid}.");

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

    private function markImportFailed(ImportRun $import, Throwable $exception): void
    {
        $reason = trim($exception->getMessage()) ?: 'неизвестная ошибка.';
        $message = 'Синхронизация каталога не выполнена: '.$reason;
        $import->forceFill([
            'status' => 'failed',
            'error_class' => $exception::class,
            'error_message' => $message,
            'finished_at' => now(),
        ])->save();
        ImportRunStage::query()
            ->where('import_run_id', $import->id)
            ->whereIn('status', ['queued', 'running'])
            ->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
    }

    private function runSynchronously(int $webhookId, string $tenant, ImportRun $import): void
    {
        for ($attempt = 1; $attempt <= self::MAX_SYNCHRONOUS_ATTEMPTS; $attempt++) {
            SyncMarketplaceCatalogJob::dispatchSync($webhookId, $tenant, $import->id);
            $status = $import->fresh();

            if (! $status || ! in_array($status->status, ['queued', 'running'], true)) {
                return;
            }
            if (! str_contains((string) $status->error_message, 'Временная ошибка связи')) {
                return;
            }
            if ($attempt < self::MAX_SYNCHRONOUS_ATTEMPTS) {
                $this->components->warn("Импорт #{$import->id}: повтор синхронного запуска ".($attempt + 1).' из '.self::MAX_SYNCHRONOUS_ATTEMPTS.'.');
            }
        }

        $status = $import->fresh();
        if ($status && in_array($status->status, ['queued', 'running'], true)) {
            $this->markImportFailed($status, new RuntimeException('Исчерпаны повторные попытки синхронного запуска.'));
        }
    }
}
