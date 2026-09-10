<?php

declare(strict_types=1);

namespace App\Models;

final class TypeWarehouse extends BaseModel
{
    protected $table = 'wms.type_warehouses';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
