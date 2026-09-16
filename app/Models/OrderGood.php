<?php
declare(strict_types=1);
namespace App\Models;
final class OrderGood extends BaseModel { protected $table = 'wms.order_goods'; protected $guarded = ['id']; protected $casts = ['src' => 'array', 'need_marking' => 'boolean']; }
