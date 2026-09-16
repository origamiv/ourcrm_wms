<?php
declare(strict_types=1);
namespace App\Models;
final class OrderSource extends BaseModel { protected $table = 'wms.order_sources'; protected $guarded = ['id']; protected $casts = ['src' => 'array', 'status' => 'integer']; }
