<?php declare(strict_types=1); namespace App\Models; class TaskStatus extends BaseModel { protected $table='wms.task_statuses'; protected $guarded=['*']; protected $casts=['status'=>'integer']; }
