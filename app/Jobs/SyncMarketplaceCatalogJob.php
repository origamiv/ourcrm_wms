<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Models\IntegrationData;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use App\Services\IntegrationBuilder;
use App\Services\MarketplaceCatalogDispatchService;
use App\Services\MarketplaceConcurrencyService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

final class SyncMarketplaceCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUEUES = [
        'wildberries' => 'imports_wildberries',
        'ozon' => 'imports_ozon',
        'yandex_market' => 'imports_yandex_market',
    ];

    private const MAX_TRANSIENT_ATTEMPTS = 5;

    public int $tries = 0;

    public int $timeout = 1200;

    public bool $failOnTimeout = true;

    public function __construct(public int $webhookId, public string $tenant, public ?int $importId = null) {}

    public static function queueForMarketplace(string $marketplace): string
    {
        return self::QUEUES[$marketplace] ?? 'imports';
    }

    public function handle(): void
    {
        $singlePage = $this->job !== null && ! ($this->job instanceof SyncJob);
        $webhook = IntegrationWebhook::query()->where('tenant_id', $this->tenant)->with(['service_obj', 'client_obj'])->findOrFail($this->webhookId);
        $service = mb_strtolower((string) ($webhook->service_obj?->shortname ?? $webhook->service_obj?->name));
        $marketplace = match (true) {
            str_contains($service, 'ozon') => 'ozon',
            str_contains($service, 'yandex') && str_contains($service, 'market') => 'yandex_market',
            str_contains($service, 'wildberries') || preg_match('/(^|_)wb($|_)/', $service) === 1 => 'wildberries',
            default => throw new InvalidArgumentException('Сервис интеграции не является поддерживаемым маркетплейсом: '.$service),
        };
        $marketplaceName = match ($marketplace) {
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            default => 'Яндекс Маркет',
        };
        $run = $this->importId
            ? ImportRun::query()->where('tenant_id', $this->tenant)->findOrFail($this->importId)
            : app(MarketplaceCatalogDispatchService::class)->dispatch($webhook, $marketplace, enqueue: false);
        if ((int) $run->source_webhook_id !== (int) $webhook->id) {
            return;
        }
        $this->importId = (int) $run->id;
        if ($this->importId !== null && ! in_array($run->status, ['queued', 'running'], true)) {
            return;
        }
        $stage = ImportRunStage::query()->where('import_run_id', $run->id)->where('stage_key', 'catalog')->firstOrFail();
        if (IntegrationBuilder::hasSchema($webhook) && ! IntegrationBuilder::hasCatalog($webhook)) {
            $message = 'Блок синхронизации каталога удалён из интеграции.';
            $stage->forceFill(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()])->save();
            $run->forceFill(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()])->save();

            return;
        }
        $locks = null;
        $accountId = (int) (($webhook->params ?? [])['account_id'] ?? 0);

        try {
            if ($accountId > 0) {
                $locks = app(MarketplaceConcurrencyService::class)->acquire($marketplace, $this->tenant, $accountId, $singlePage);
            }
            $run->refresh();
            if (! in_array($run->status, ['queued', 'running'], true)) {
                return;
            }
            $activeRuns = ImportRun::query()
                ->where('tenant_id', $this->tenant)
                ->where('project', 'marketplace')
                ->where(function ($query) use ($webhook, $accountId): void {
                    $query->where('source_webhook_id', $webhook->id);
                    if ($accountId > 0) {
                        $query->orWhere('source_account_id', $accountId);
                    }
                })
                ->whereIn('status', ['queued', 'running'])
                ->orderBy('id')
                ->get();
            $canonicalRun = $activeRuns->first();
            if ($canonicalRun && (int) $canonicalRun->id !== (int) $run->id) {
                $this->skipDuplicate($run, $stage, $canonicalRun);

                return;
            }
            foreach ($activeRuns->skip(1) as $duplicateRun) {
                $this->skipDuplicate($duplicateRun, $duplicateRun->stages()->where('stage_key', 'catalog')->first(), $run);
            }
            $stage->forceFill(['status' => 'running', 'error_message' => null, 'started_at' => $stage->started_at ?: now()])->save();
            $run->forceFill(['status' => 'running', 'error_message' => null, 'error_class' => null, 'current_stage' => 'catalog', 'started_at' => $run->started_at ?: now(), 'last_job_id' => $this->job?->getJobId()])->save();
            $data = new IntegrationData;
            $data->forceFill(['name' => $webhook->name, 'shortname' => 'catalog_'.str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 'webhook_id' => $webhook->id, 'service_id' => $webhook->service_id, 'status' => 0, 'status_processing' => 0, 'tenant_id' => $this->tenant, 'src' => ['webhook_id' => $webhook->id]])->save();
            $ruleIds = array_values(array_filter((array) $webhook->rules_id));
            if ($ruleIds === [] || IntegrationBuilder::hasSchema($webhook)) {
                $ruleIds = [IntegrationRule::query()->where('shortname', match ($marketplace) {
                    'ozon' => 'ozon_catalog',
                    'yandex_market' => 'yandex_market_catalog',
                    default => 'wildberries_catalog',
                })->value('id')];
            }
            foreach ($ruleIds as $ruleId) {
                $rule = IntegrationRule::query()->findOrFail((int) $ruleId);
                $handler = ltrim((string) $rule->val, '\\');
                if (! str_contains($handler, '\\')) {
                    $handler = 'App\\Rules\\'.$handler;
                }
                app($handler)->handle($data, [
                    'marketplace' => $marketplace,
                    'rule_id' => $rule->id,
                    'ozon_last_id' => $marketplace === 'ozon' ? (($run->options ?? [])['catalog_last_id'] ?? null) : null,
                    'initial_processed' => (int) (($run->options ?? [])['catalog_processed'] ?? 0),
                    'cursor' => ($run->options ?? [])['catalog_cursor'] ?? null,
                    'single_page' => $singlePage,
                    'progress' => function (int $total, int $processed) use ($run, $stage): void {
                        $run->forceFill(['total_records' => $total, 'processed_records' => $processed])->save();
                        $stage->forceFill(['total_records' => $total, 'processed_records' => $processed])->save();
                    },
                    'checkpoint' => $marketplace === 'ozon' && ! $singlePage ? function (?string $lastId) use ($run): void {
                        $options = (array) ($run->options ?? []);
                        if ($lastId === null || $lastId === '') {
                            unset($options['catalog_last_id']);
                        } else {
                            $options['catalog_last_id'] = $lastId;
                        }
                        $options['catalog_processed'] = (int) $run->processed_records;
                        $run->forceFill(['options' => $options])->save();
                    } : null,
                ]);
            }
            if ($singlePage && ($data->data['next_cursor'] ?? null) !== null) {
                DB::transaction(function () use ($run, $stage, $data, $marketplace): void {
                    $options = (array) $run->options;
                    $options['catalog_cursor'] = $data->data['next_cursor'];
                    $options['catalog_processed'] = (int) $data->data['processed'];
                    unset($options['catalog_network_attempts']);
                    $run->forceFill(['status' => 'queued', 'options' => $options])->save();
                    $stage->forceFill(['status' => 'queued'])->save();
                    self::dispatch($this->webhookId, $this->tenant, (int) $run->id)
                        ->onConnection('redis')->onQueue(self::queueForMarketplace($marketplace))->delay(1)->afterCommit();
                });

                return;
            }
            if ($marketplace === 'ozon') {
                $options = (array) ($run->options ?? []);
                unset($options['catalog_last_id']);
                $run->forceFill(['options' => $options])->save();
            }
            $processed = (int) ($data->data['processed'] ?? 0);
            $stage->forceFill(['status' => 'completed', 'finished_at' => now(), 'processed_records' => $processed, 'total_records' => max((int) $stage->total_records, $processed)])->save();
            $run->forceFill(['status' => 'completed', 'current_stage' => null, 'completed_stages' => 1, 'finished_at' => now(), 'processed_records' => $processed, 'total_records' => max((int) $run->total_records, $processed)])->save();
        } catch (Throwable $exception) {
            $reason = trim($exception->getMessage()) ?: 'неизвестная ошибка.';
            $message = 'Синхронизация каталога '.$marketplaceName.' не выполнена: '.$reason;
            if ($singlePage && $exception instanceof LockTimeoutException) {
                $stage->forceFill(['status' => 'queued', 'error_message' => 'Ожидание освобождения аккаунта.', 'started_at' => null])->save();
                $run->forceFill(['status' => 'queued', 'error_message' => 'Ожидание освобождения аккаунта.', 'started_at' => null])->save();
                $this->release(60);

                return;
            }
            $options = (array) ($run->options ?? []);
            $networkAttempts = (int) ($options['catalog_network_attempts'] ?? 0) + 1;
            if ($singlePage && $this->isTransient($exception) && $networkAttempts < self::MAX_TRANSIENT_ATTEMPTS) {
                $retryMessage = 'Временная ошибка связи с '.$marketplaceName.'. Повторная попытка '
                    .($networkAttempts + 1).' из '.self::MAX_TRANSIENT_ATTEMPTS.'.';
                $options['catalog_network_attempts'] = $networkAttempts;
                $stage->forceFill([
                    'status' => 'queued',
                    'started_at' => null,
                    'error_message' => $retryMessage,
                    'finished_at' => null,
                ])->save();
                $run->forceFill([
                    'status' => 'queued',
                    'current_stage' => 'catalog',
                    'started_at' => null,
                    'error_message' => $retryMessage,
                    'finished_at' => null,
                    'options' => $options,
                ])->save();
                $this->release(min(300, 30 * (2 ** max(0, $networkAttempts - 1))));

                return;
            }
            $stage->forceFill(['status' => 'failed', 'finished_at' => now(), 'error_message' => $message])->save();
            $run->forceFill(['status' => 'failed', 'error_class' => $exception::class, 'error_message' => $message, 'finished_at' => now()])->save();
            if ($singlePage) {
                $this->fail($exception);

                return;
            }
            throw $exception;
        } finally {
            if ($locks !== null) {
                app(MarketplaceConcurrencyService::class)->release($locks);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->importId === null) {
            return;
        }

        $run = ImportRun::query()
            ->where('tenant_id', $this->tenant)
            ->whereKey($this->importId)
            ->whereIn('status', ['queued', 'running'])
            ->first();
        if (! $run) {
            return;
        }

        $reason = trim($exception->getMessage()) ?: 'неизвестная ошибка.';
        $message = 'Задача синхронизации каталога аварийно завершена: '.$reason;
        $run->forceFill([
            'status' => 'failed',
            'error_class' => $exception::class,
            'error_message' => $message,
            'finished_at' => now(),
        ])->save();
        ImportRunStage::query()
            ->where('import_run_id', $run->id)
            ->whereIn('status', ['queued', 'running'])
            ->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
    }

    private function skipDuplicate(ImportRun $run, ?ImportRunStage $stage, ImportRun $canonicalRun): void
    {
        $message = 'Импорт пропущен: уже существует активный импорт #'.$canonicalRun->id.' для этой интеграции.';
        $run->forceFill([
            'status' => 'failed',
            'error_class' => InvalidArgumentException::class,
            'error_message' => $message,
            'finished_at' => now(),
        ])->save();
        $stage?->forceFill(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()])->save();
    }

    private function isTransient(Throwable $exception): bool
    {
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException || $exception instanceof LockTimeoutException) {
            return true;
        }

        return $exception instanceof \Illuminate\Http\Client\RequestException
            && in_array($exception->response->status(), [408, 425, 429, 500, 502, 503, 504], true);
    }
}
