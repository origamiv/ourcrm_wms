<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Cell extends BaseModel
{
    protected $table = 'wms.cells';

    protected $guarded = ['*'];

    protected $casts = [
        'warehouse_id' => 'integer',
        'zone_id' => 'integer',
        'row' => 'integer',
        'level' => 'integer',
        'number' => 'integer',
        'status' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
