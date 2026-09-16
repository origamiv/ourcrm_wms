<?php
declare(strict_types=1);
namespace App\Models;
final class OrderHistory extends BaseModel { protected $table = 'wms.order_histories'; protected $guarded = ['id']; protected $casts = ['src' => 'array']; }
