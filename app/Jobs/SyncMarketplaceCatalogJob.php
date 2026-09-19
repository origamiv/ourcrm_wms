<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportRun;
use App\Models\ImportRunStage;
use App\Models\IntegrationData;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Throwable;

final class SyncMarketplaceCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $webhookId, public string $tenant) {}

    public function handle(): void
    {
        $webhook = IntegrationWebhook::query()->where('tenant_id', $this->tenant)->findOrFail($this->webhookId);
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
        $run = ImportRun::query()->create([
            'tenant_id' => $this->tenant,
            'source_client_id' => (int) ($webhook->params['account_id'] ?? 0) ?: null,
            'source_system' => $marketplace,
            'project' => 'marketplace',
            'name' => 'Синхронизация каталога '.$marketplaceName.' — '.$webhook->name,
            'status' => 'running',
            'current_stage' => 'catalog',
            'total_stages' => 1,
            'completed_stages' => 0,
            'started_at' => now(),
            'options' => ['webhook_id' => $webhook->id, 'marketplace' => $marketplace],
        ]);
        $stage = ImportRunStage::query()->create([
            'import_run_id' => $run->id,
            'stage_number' => 1,
            'stage_key' => 'catalog',
            'name' => 'Каталог '.$marketplaceName,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
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
                app($handler)->handle($data, ['marketplace' => $marketplace, 'rule_id' => $rule->id]);
            }
            $stage->forceFill(['status' => 'completed', 'finished_at' => now(), 'processed_records' => (int) ($data->data['processed'] ?? 0), 'total_records' => (int) ($data->data['processed'] ?? 0)])->save();
            $run->forceFill(['status' => 'completed', 'current_stage' => null, 'completed_stages' => 1, 'finished_at' => now(), 'processed_records' => (int) ($data->data['processed'] ?? 0), 'total_records' => (int) ($data->data['processed'] ?? 0)])->save();
        } catch (Throwable $exception) {
            $message = 'Синхронизация каталога '.$marketplaceName.' не выполнена: '.trim($exception->getMessage()) ?: 'неизвестная ошибка.';
            $stage->forceFill(['status' => 'failed', 'finished_at' => now(), 'error_message' => $message])->save();
            $run->forceFill(['status' => 'failed', 'error_class' => $exception::class, 'error_message' => $message, 'finished_at' => now()])->save();
            throw $exception;
        }
    }
}
