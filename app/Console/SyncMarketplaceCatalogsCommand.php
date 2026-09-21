<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\SyncMarketplaceCatalogJob;
use App\Models\ClientAccount;
use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use App\Models\TenantSetting;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SyncMarketplaceCatalogsCommand extends Command
{
    public const FEATURE = 'integration.marketplace_catalog_sync';

    private const MAX_SYNCHRONOUS_ATTEMPTS = 5;

    private const RULES = [
        'wildberries' => 'wildberries_catalog',
        'ozon' => 'ozon_catalog',
        'yandex_market' => 'yandex_market_catalog',
    ];

    protected $signature = 'integration:sync-catalogs
                            {--marketplace= : Фильтр площадки: wildberries, ozon или yandex_market}
                            {--limit=100 : Максимальное количество вебхуков}
                            {--tenant= : Ограничить запуск одной организацией}';

    protected $description = 'Синхронно запустить каталоги маркетплейсов для организаций с включённой фичей';

    public function handle(): int
    {
        $marketplaceFilter = $this->option('marketplace');
        $tenantFilter = $this->option('tenant');
        $limit = (int) $this->option('limit');

        if ($marketplaceFilter !== null && ! array_key_exists((string) $marketplaceFilter, self::RULES)) {
            $this->components->error('Недопустимая площадка. Используйте: wildberries, ozon или yandex_market.');

            return self::INVALID;
        }
        if ($limit < 1) {
            $this->components->error('Параметр --limit должен быть положительным числом.');

            return self::INVALID;
        }

        $tenants = DB::table('public.tenants')
            ->where('status', 1)
            ->when($tenantFilter !== null, fn ($query) => $query->where('id', (string) $tenantFilter))
            ->whereIn('id', TenantSetting::query()
                ->where('name', self::FEATURE)
                ->whereRaw("value->>'enabled' = 'true'")
                ->select('tenant_id'))
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        if ($tenants === []) {
            $this->components->info('Организаций с включённой синхронизацией не найдено.');

            return self::SUCCESS;
        }

        $rules = IntegrationRule::query()
            ->where('status', 1)
            ->whereIn('shortname', array_values($marketplaceFilter === null
                ? self::RULES
                : [self::RULES[(string) $marketplaceFilter]]))
            ->whereHas('typeProcessing_obj', fn ($query) => $query->where('status', 1)->where('shortname', 'marketplace_catalog'))
            ->get()
            ->keyBy('shortname');

        IntegrationWebhook::query()
            ->whereIn('tenant_id', $tenants)
            ->where('status', 3)
            ->whereNotNull('dat_last_run')
            ->where('dat_last_run', '<=', now()->subHours(4))
            ->update(['status' => 1]);

        $accounts = ClientAccount::query()
            ->whereIn('tenant_id', $tenants)
            ->where('status', 1)
            ->get()
            ->keyBy(fn (ClientAccount $account): string => $account->tenant_id.':'.$account->id);

        $webhooks = IntegrationWebhook::query()
            ->whereIn('tenant_id', $tenants)
            ->where('status', 1)
            ->with(['service_obj', 'client_obj'])
            ->orderByRaw('dat_last_run ASC NULLS FIRST')
            ->orderBy('id')
            ->get();

        $counters = ['completed' => 0, 'failed' => 0, 'blocked' => 0, 'skipped' => 0];
        foreach ($webhooks as $webhook) {
            if ($limit <= $counters['completed'] + $counters['failed'] + $counters['blocked']) {
                break;
            }

            $marketplace = $this->marketplaceFor($webhook);
            if ($marketplace === null || ($marketplaceFilter !== null && $marketplace !== $marketplaceFilter)) {
                continue;
            }

            $rule = $rules->get(self::RULES[$marketplace]);
            $configuredRuleIds = array_map('intval', array_filter((array) $webhook->rules_id));
            if (! $rule || ($configuredRuleIds !== [] && ! in_array((int) $rule->id, $configuredRuleIds, true))) {
                continue;
            }

            $accountId = (int) (($webhook->params ?? [])['account_id'] ?? 0);
            $account = $accounts->get($webhook->tenant_id.':'.$accountId);
            if (! $account || ($webhook->client_id !== null && (int) $account->client_id !== (int) $webhook->client_id)) {
                continue;
            }

            $activeImport = ImportRun::query()
                ->where('tenant_id', $webhook->tenant_id)
                ->where('project', 'marketplace')
                ->whereIn('status', ['queued', 'running'])
                ->where(function ($query) use ($webhook, $accountId): void {
                    $query->where('source_webhook_id', $webhook->id)->orWhere('source_account_id', $accountId);
                })
                ->exists();
            if ($activeImport) {
                $counters['skipped']++;

                continue;
            }

            $this->recoverStaleWebhook($webhook);
            $claimed = $this->claimWebhook((int) $webhook->id, (string) $webhook->tenant_id);
            if (! $claimed) {
                $counters['skipped']++;

                continue;
            }

            $session = $this->startSession($account, $claimed, $marketplace);
            $import = null;
            try {
                if (! $this->credentialsConfigured($account, $marketplace)) {
                    $reason = 'Не заполнены обязательные реквизиты аккаунта.';
                    $this->blockAccount($account, $reason);
                    $this->finishSession($session, 'blocked', 0, $reason);
                    $this->setWebhookStatus($claimed, 1);
                    $counters['blocked']++;

                    continue;
                }

                $import = $this->createImport($claimed, $marketplace);
                $this->runSynchronously($claimed, $import);
                $processed = (int) ($import->fresh()?->processed_records ?? 0);
                $this->finishSession($session, 'completed', $processed);
                $this->setWebhookStatus($claimed, 1);
                $counters['completed']++;
            } catch (Throwable $exception) {
                $reason = trim($exception->getMessage()) ?: 'неизвестная ошибка.';
                $processed = (int) ($import?->fresh()?->processed_records ?? 0);
                if ($this->isInvalidToken($exception)) {
                    $this->blockAccount($account, $reason);
                    $this->finishSession($session, 'blocked', $processed, $reason);
                    $counters['blocked']++;
                } else {
                    $this->finishSession($session, 'failed', $processed, $reason);
                    $counters['failed']++;
                }
                $this->setWebhookStatus($claimed, 1);
            }
        }

        $this->components->info(sprintf(
            'Завершено: %d; ошибки: %d; заблокировано аккаунтов: %d; пропущено: %d.',
            $counters['completed'],
            $counters['failed'],
            $counters['blocked'],
            $counters['skipped'],
        ));

        return self::SUCCESS;
    }

    private function claimWebhook(int $webhookId, string $tenant): ?IntegrationWebhook
    {
        return DB::transaction(function () use ($webhookId, $tenant): ?IntegrationWebhook {
            $webhook = IntegrationWebhook::query()->where('tenant_id', $tenant)->lockForUpdate()->find($webhookId);
            if (! $webhook || (int) $webhook->status !== 1) {
                return null;
            }
            $webhook->forceFill(['status' => 3, 'dat_last_run' => now()])->save();

            return $webhook->fresh(['service_obj', 'client_obj']);
        });
    }

    private function recoverStaleWebhook(IntegrationWebhook $webhook): void
    {
        if ((int) $webhook->status !== 3 || ! $webhook->dat_last_run || $webhook->dat_last_run->greaterThan(now()->subHours(4))) {
            return;
        }

        IntegrationWebhook::query()
            ->whereKey($webhook->id)
            ->where('tenant_id', $webhook->tenant_id)
            ->where('status', 3)
            ->update(['status' => 1]);
    }

    private function setWebhookStatus(IntegrationWebhook $webhook, int $status): void
    {
        IntegrationWebhook::query()->whereKey($webhook->id)->where('tenant_id', $webhook->tenant_id)->update(['status' => $status]);
    }

    private function blockAccount(ClientAccount $account, string $reason): void
    {
        $account->forceFill(['status' => 2])->save();
        $this->components->warn('Аккаунт #'.$account->id.' заблокирован: '.$reason);
    }

    private function startSession(ClientAccount $account, IntegrationWebhook $webhook, string $marketplace): array
    {
        $sessionId = now()->format('YmdHis').'-'.Str::lower(Str::random(12));
        $key = $this->sessionKey((string) $account->id, (string) $webhook->id, $sessionId);
        Redis::connection()->hmset($key, [
            'account_id' => (string) $account->id,
            'webhook_id' => (string) $webhook->id,
            'marketplace' => $marketplace,
            'started_at' => now()->toIso8601String(),
            'status' => 'running',
            'processed_records' => '0',
        ]);
        Redis::connection()->expire($key, 30 * 86400);

        return ['key' => $key];
    }

    private function finishSession(array $session, string $status, int $processed, ?string $error = null): void
    {
        $values = [
            'finished_at' => now()->toIso8601String(),
            'status' => $status,
            'processed_records' => (string) $processed,
        ];
        if ($error !== null) {
            $values['error'] = mb_substr($error, 0, 2000);
        }
        Redis::connection()->hmset($session['key'], $values);
        Redis::connection()->expire($session['key'], 30 * 86400);
    }

    private function sessionKey(string $accountId, string $webhookId, string $sessionId): string
    {
        return 'marketplace_catalog:account:'.$accountId.':webhook:'.$webhookId.':session:'.$sessionId;
    }

    private function createImport(IntegrationWebhook $webhook, string $marketplace): ImportRun
    {
        $accountId = (int) (($webhook->params ?? [])['account_id'] ?? 0) ?: null;
        $options = [
            'webhook_id' => (int) $webhook->id,
            'marketplace' => $marketplace,
            'account_id' => $accountId,
            'client_id' => $webhook->client_id,
            'client_name' => $webhook->client_obj?->name,
        ];

        return DB::transaction(function () use ($webhook, $marketplace, $accountId, $options): ImportRun {
            $import = ImportRun::query()->create([
                'tenant_id' => $webhook->tenant_id,
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
    }

    private function runSynchronously(IntegrationWebhook $webhook, ImportRun $import): void
    {
        for ($attempt = 1; $attempt <= self::MAX_SYNCHRONOUS_ATTEMPTS; $attempt++) {
            SyncMarketplaceCatalogJob::dispatchSync((int) $webhook->id, (string) $webhook->tenant_id, (int) $import->id);
            $status = $import->fresh();
            if (! $status || ! in_array($status->status, ['queued', 'running'], true)) {
                return;
            }
            if (! str_contains((string) $status->error_message, 'Временная ошибка связи') || $attempt === self::MAX_SYNCHRONOUS_ATTEMPTS) {
                throw new RuntimeException((string) ($status->error_message ?: 'Синхронизация каталога завершилась ошибкой.'));
            }
        }
    }

    private function credentialsConfigured(ClientAccount $account, string $marketplace): bool
    {
        $token = trim((string) $account->token);
        $credentials = (array) ($account->src['credentials'] ?? []);

        return match ($marketplace) {
            'wildberries' => $token !== '',
            'ozon' => $token !== '' && trim((string) ($credentials['key2'] ?? '')) !== '',
            'yandex_market' => $token !== '' && trim((string) ($credentials['business_id'] ?? '')) !== '',
            default => false,
        };
    }

    private function isInvalidToken(Throwable $exception): bool
    {
        return $exception instanceof RequestException
            && in_array($exception->response->status(), [401, 403], true);
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
