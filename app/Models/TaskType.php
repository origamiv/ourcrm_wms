<?php declare(strict_types=1); namespace App\Models; class TaskType extends BaseModel { protected $table='wms.task_types'; protected $guarded=['*']; protected $casts=['status'=>'integer']; }
