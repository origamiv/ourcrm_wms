<?php
declare(strict_types=1);
namespace App\Models;
final class Shipment extends BaseModel { protected $table = 'wms.shipments'; protected $guarded = ['id']; protected $casts = ['src' => 'array']; }
