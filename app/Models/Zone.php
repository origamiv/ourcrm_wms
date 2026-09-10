<?php

declare(strict_types=1);

namespace App\Models;

final class Zone extends BaseModel
{
    protected $table = 'wms.zones';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
