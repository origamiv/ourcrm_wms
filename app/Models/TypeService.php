<?php

declare(strict_types=1);

namespace App\Models;

final class TypeService extends BaseModel
{
    protected $table = 'wms.type_services';
    protected $guarded = ['*'];
    protected $casts = ['status' => 'integer'];
}
