<?php
declare(strict_types=1);
namespace App\Models;
final class OrderCancelStatus extends BaseModel { protected $table = 'wms.order_cancel_statuses'; protected $guarded = ['id']; protected $casts = ['src' => 'array', 'status' => 'integer']; }
