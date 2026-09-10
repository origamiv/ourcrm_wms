<?php

declare(strict_types=1);

namespace App\Models;

final class GoodCard extends BaseModel
{
    protected $table = 'goods.good_cards';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];

    public function good(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Good::class, 'good_id');
    }
}
