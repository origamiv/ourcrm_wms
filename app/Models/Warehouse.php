<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Warehouse extends BaseModel
{
    protected $table = 'wms.warehouses';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];

    public function typeWarehouse(): BelongsTo
    {
        return $this->belongsTo(TypeWarehouse::class, 'type_warehouse_id');
    }
}
