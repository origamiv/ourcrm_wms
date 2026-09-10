<?php

declare(strict_types=1);

namespace App\Models;

final class IntegrationData extends BaseModel
{
    protected $table = 'integration.data';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'src' => 'array', 'data' => 'array', 'progress_processing' => 'array', 'status_processing' => 'integer'];

    public function webhook_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationWebhook::class, 'webhook_id');
    }

    public function service_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationService::class, 'service_id');
    }
}
