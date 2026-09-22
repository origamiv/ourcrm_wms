<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

final class IntegrationWebhook extends BaseModel
{
    protected $table = 'integration.webhooks';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'client_id' => 'integer', 'rules_id' => 'array', 'params' => 'array', 'cnt' => 'integer', 'dat_last_run' => 'datetime'];

    protected $appends = ['count_runs'];

    public function getCountRunsAttribute(): int
    {
        if (array_key_exists('runs_count', $this->attributes)) {
            return (int) $this->attributes['runs_count'];
        }

        if (! Schema::hasTable('wms.import_runs')) {
            return 0;
        }

        $start = CarbonImmutable::now('UTC')->startOfDay();

        return $this->runs()
            ->where('tenant_id', $this->tenant_id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $start->addDay())
            ->count();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ImportRun::class, 'source_webhook_id');
    }

    public function client_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function service_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationService::class, 'service_id');
    }

    public function typeHook_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationHookType::class, 'type_hook_id');
    }
}
