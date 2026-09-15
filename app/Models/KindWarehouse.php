<?php

declare(strict_types=1);

namespace App\Models;

final class KindWarehouse extends BaseModel
{
    protected $table = 'wms.kind_warehouses';
    protected $guarded = ['*'];
    protected $casts = ['status' => 'integer'];
}
