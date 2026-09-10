<?php

declare(strict_types=1);

namespace App\Models;

final class DeliveryService extends BaseModel
{
    protected $table = 'wms.delivery_services';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'is_order_edit' => 'integer'];

    public function marketplace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }
}
