<?php
declare(strict_types=1);
namespace App\Models;
final class OrderStatus extends BaseModel { protected $table = 'wms.order_statuses'; protected $guarded = ['id']; protected $casts = ['src' => 'array', 'status' => 'integer']; }
