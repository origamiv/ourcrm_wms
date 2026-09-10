<?php

declare(strict_types=1);

namespace App\Models;

final class GoodUnit extends BaseModel
{
    protected $table = 'goods.unit_goods';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
