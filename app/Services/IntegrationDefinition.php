<?php

declare(strict_types=1);

namespace App\Services;

final class IntegrationDefinition
{
    public static function get(string $catalog): array
    {
        $definitions = [
            'webhooks' => ['model' => \App\Models\IntegrationWebhook::class, 'fields' => ['name', 'shortname', 'status', 'service_id', 'type_hook_id', 'url', 'rules_id', 'cnt', 'dat_last_run', 'params'], 'details' => ['url', 'params'], 'links' => ['service_id' => \App\Models\IntegrationService::class, 'type_hook_id' => \App\Models\IntegrationHookType::class]],
            'data' => ['model' => \App\Models\IntegrationData::class, 'fields' => ['name', 'shortname', 'status', 'webhook_id', 'service_id', 'raw', 'src', 'data', 'progress_processing', 'status_processing'], 'details' => ['raw', 'src', 'data', 'progress_processing'], 'links' => ['webhook_id' => \App\Models\IntegrationWebhook::class, 'service_id' => \App\Models\IntegrationService::class]],
            'rules' => ['model' => \App\Models\IntegrationRule::class, 'fields' => ['name', 'shortname', 'status', 'type_processing_id', 'val', 'params'], 'details' => ['val', 'params'], 'links' => ['type_processing_id' => \App\Models\IntegrationProcessingType::class]],
            'services' => ['model' => \App\Models\IntegrationService::class, 'fields' => ['name', 'shortname', 'status'], 'details' => [], 'links' => []],
            'type_hook' => ['model' => \App\Models\IntegrationHookType::class, 'fields' => ['name', 'shortname', 'status'], 'details' => [], 'links' => []],
            'type_processing' => ['model' => \App\Models\IntegrationProcessingType::class, 'fields' => ['name', 'shortname', 'status'], 'details' => [], 'links' => []],
        ];
        abort_unless(isset($definitions[$catalog]), 404);

        return $definitions[$catalog];
    }
}
