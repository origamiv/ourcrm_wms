<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\IntegrationData;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncMarketplaceCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $webhookId, public string $tenant) {}

    public function handle(): void
    {
        $webhook = IntegrationWebhook::query()->where('tenant_id', $this->tenant)->findOrFail($this->webhookId);
        $service = mb_strtolower((string) ($webhook->service_obj?->shortname ?? $webhook->service_obj?->name));
        $marketplace = str_contains($service, 'ozon') ? 'ozon' : 'wildberries';
        $data = new IntegrationData;
        $data->forceFill(['name' => $webhook->name, 'shortname' => $webhook->shortname.'_'.now()->format('YmdHis'), 'webhook_id' => $webhook->id, 'service_id' => $webhook->service_id, 'status' => 0, 'status_processing' => 0, 'tenant_id' => $this->tenant, 'src' => ['webhook_id' => $webhook->id]])->save();
        $ruleIds = array_values(array_filter((array) $webhook->rules_id));
        if ($ruleIds === []) {
            $ruleIds = [IntegrationRule::query()->where('shortname', $marketplace === 'ozon' ? 'ozon_catalog' : 'wildberries_catalog')->value('id')];
        }
        foreach ($ruleIds as $ruleId) {
            $rule = IntegrationRule::query()->findOrFail((int) $ruleId);
            $handler = ltrim((string) $rule->val, '\\');
            if (! str_contains($handler, '\\')) {
                $handler = 'App\\Rules\\'.$handler;
            }
            app($handler)->handle($data, ['marketplace' => $marketplace, 'rule_id' => $rule->id]);
        }
    }
}
