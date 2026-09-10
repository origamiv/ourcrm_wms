<?php

declare(strict_types=1);

namespace App\Models;

final class Good extends BaseModel
{
    protected $table = 'goods.goods';

    protected $guarded = ['*'];

    protected $casts = ['has_children' => 'boolean', 'category_manual' => 'boolean', 'articul' => 'array', 'barcodes' => 'array', 'status' => 'integer', 'level' => 'integer', 'is_category' => 'integer', 'is_from_external' => 'integer'];

    public function cards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GoodCard::class, 'good_id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function goodcard(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GoodCard::class, 'goodcard_id');
    }

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GoodType::class, 'type_good');
    }

    public function unit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GoodUnit::class, 'type_unit');
    }
}
