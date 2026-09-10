<?php

declare(strict_types=1);

namespace App\Models;

final class GoodType extends BaseModel
{
    protected $table = 'goods.type_goods';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
