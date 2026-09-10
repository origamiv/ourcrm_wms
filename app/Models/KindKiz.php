<?php

declare(strict_types=1);

namespace App\Models;

final class KindKiz extends BaseModel
{
    protected $table = 'goods.kind_kiz';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
