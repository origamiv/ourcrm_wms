<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CellGood extends BaseModel
{
    protected $table = 'wms.cell_goods';

    protected $guarded = ['*'];

    protected $casts = [
        'warehouse_id' => 'integer',
        'cell_id' => 'integer',
        'cnt' => 'integer',
        'put_at' => 'datetime',
        'leave_at' => 'datetime',
        'src' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(Cell::class);
    }
}
