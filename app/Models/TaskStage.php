<?php
declare(strict_types=1); namespace App\Models;
final class TaskStage extends BaseModel { protected $table='wms.task_stages'; protected $guarded=['*']; protected $casts=['status'=>'integer','src'=>'array']; }
