<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends BaseModel
{
    protected $table = 'wms.orders';
    protected $guarded = ['id'];
    protected $casts = ['custom' => 'array', 'src' => 'array', 'need_imei' => 'boolean', 'need_uin' => 'boolean', 'need_gtin' => 'boolean', 'need_sgtin' => 'boolean', 'need_expiration' => 'boolean', 'need_gtd' => 'boolean', 'is_b2b' => 'boolean', 'is_crossborder' => 'boolean'];
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function goods(): HasMany { return $this->hasMany(OrderGood::class); }
    public function histories(): HasMany { return $this->hasMany(OrderHistory::class); }
}
