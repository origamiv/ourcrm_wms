<?php declare(strict_types=1); namespace App\Models; class Priority extends BaseModel { protected $table='wms.priorities'; protected $guarded=['*']; protected $casts=['status'=>'integer']; }
