<?php
declare(strict_types=1);
namespace App\Models;
final class ShipmentStatus extends BaseModel { protected $table = 'wms.shipment_statuses'; protected $guarded = ['id']; protected $casts = ['src' => 'array', 'status' => 'integer']; }
