<?php

declare(strict_types=1);

namespace App\Models;

final class IntegrationWebhook extends BaseModel
{
    protected $table = 'integration.webhooks';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'rules_id' => 'array', 'params' => 'array', 'cnt' => 'integer', 'dat_last_run' => 'datetime'];

    public function service_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationService::class, 'service_id');
    }

    public function typeHook_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationHookType::class, 'type_hook_id');
    }
}
