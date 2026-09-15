<?php
declare(strict_types=1); namespace App\Models;
final class TaskGood extends BaseModel { protected $table='wms.task_goods'; protected $guarded=['*']; protected $casts=['src'=>'array']; }
