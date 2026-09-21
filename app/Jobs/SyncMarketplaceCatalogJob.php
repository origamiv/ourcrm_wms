<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Models\IntegrationData;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use App\Services\MarketplaceConcurrencyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    public int $tries = 1;

    public function __construct(public int $webhookId, public string $tenant, public ?int $importId = null) {}

    public static function queueForMarketplace(string $marketplace): string
    {
        return self::QUEUES[$marketplace] ?? 'imports';
    }

    public function handle(): void
    {
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
            : $this->createLegacyImport($webhook, $marketplace, $marketplaceName);
        if ($this->importId !== null && ! in_array($run->status, ['queued', 'running'], true)) {
            return;
        }
        $stage = ImportRunStage::query()->where('import_run_id', $run->id)->where('stage_key', 'catalog')->firstOrFail();
        $locks = null;
        $accountId = (int) (($webhook->params ?? [])['account_id'] ?? 0);

        try {
            if ($accountId > 0) {
                $locks = app(MarketplaceConcurrencyService::class)->acquire($marketplace, $this->tenant, $accountId);
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
            $stage->forceFill(['status' => 'running', 'started_at' => $stage->started_at ?: now()])->save();
            $run->forceFill(['status' => 'running', 'current_stage' => 'catalog', 'started_at' => $run->started_at ?: now(), 'last_job_id' => $this->job?->getJobId()])->save();
            $data = new IntegrationData;
            $data->forceFill(['name' => $webhook->name, 'shortname' => $webhook->shortname.'_'.now()->format('YmdHis'), 'webhook_id' => $webhook->id, 'service_id' => $webhook->service_id, 'status' => 0, 'status_processing' => 0, 'tenant_id' => $this->tenant, 'src' => ['webhook_id' => $webhook->id]])->save();
            $ruleIds = array_values(array_filter((array) $webhook->rules_id));
            if ($ruleIds === []) {
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
                    'progress' => function (int $total, int $processed) use ($run, $stage): void {
                        $run->forceFill(['total_records' => $total, 'processed_records' => $processed])->save();
                        $stage->forceFill(['total_records' => $total, 'processed_records' => $processed])->save();
                    },
                ]);
            }
            $processed = (int) ($data->data['processed'] ?? 0);
            $stage->forceFill(['status' => 'completed', 'finished_at' => now(), 'processed_records' => $processed, 'total_records' => max((int) $stage->total_records, $processed)])->save();
            $run->forceFill(['status' => 'completed', 'current_stage' => null, 'completed_stages' => 1, 'finished_at' => now(), 'processed_records' => $processed, 'total_records' => max((int) $run->total_records, $processed)])->save();
        } catch (Throwable $exception) {
            $reason = trim($exception->getMessage()) ?: 'неизвестная ошибка.';
            $message = 'Синхронизация каталога '.$marketplaceName.' не выполнена: '.$reason;
            $stage->forceFill(['status' => 'failed', 'finished_at' => now(), 'error_message' => $message])->save();
            $run->forceFill(['status' => 'failed', 'error_class' => $exception::class, 'error_message' => $message, 'finished_at' => now()])->save();
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

    private function createLegacyImport(IntegrationWebhook $webhook, string $marketplace, string $marketplaceName): ImportRun
    {
        $run = ImportRun::query()->create([
            'tenant_id' => $this->tenant,
            'source_client_id' => $webhook->client_id,
            'source_webhook_id' => $webhook->id,
            'source_account_id' => (int) (($webhook->params ?? [])['account_id'] ?? 0) ?: null,
            'source_system' => $marketplace,
            'project' => 'marketplace',
            'name' => 'Синхронизация каталога '.$marketplaceName.' — '.$webhook->name,
            'status' => 'queued',
            'current_stage' => 'catalog',
            'total_stages' => 1,
            'completed_stages' => 0,
            'options' => ['webhook_id' => $webhook->id, 'marketplace' => $marketplace, 'account_id' => (int) (($webhook->params ?? [])['account_id'] ?? 0) ?: null],
        ]);
        ImportRunStage::query()->create(['import_run_id' => $run->id, 'stage_number' => 1, 'stage_key' => 'catalog', 'name' => 'Каталог '.$marketplaceName, 'status' => 'queued']);

        return $run;
    }
}
