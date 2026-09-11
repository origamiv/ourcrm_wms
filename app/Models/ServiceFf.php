<?php

declare(strict_types=1);

namespace App\Models;

final class ServiceFf extends BaseModel
{
    protected $table = 'wms.services_ff';
    protected $guarded = ['*'];
    protected $casts = ['status' => 'integer', 'unit_id' => 'integer', 'price' => 'decimal:2', 'type_service_ff' => 'integer', 'is_visible' => 'integer'];
}
