<?php

declare(strict_types=1);

namespace App\Models;

final class GoodMarketplace extends BaseModel
{
    protected $table = 'wms.goods_marketplace';

    protected $guarded = ['*'];

    protected $casts = ['barcodes' => 'array', 'raw_data' => 'array', 'status' => 'integer', 'good_id' => 'integer', 'matched_at' => 'datetime', 'synced_at' => 'datetime'];

    public function good(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Good::class, 'good_id');
    }

    public function webhook(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationWebhook::class, 'webhook_id');
    }
}
