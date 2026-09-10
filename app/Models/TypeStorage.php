<?php

declare(strict_types=1);

namespace App\Models;

final class TypeStorage extends BaseModel
{
    protected $table = 'wms.type_storage';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
