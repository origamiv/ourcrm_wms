<?php

declare(strict_types=1);

namespace App\Models;

class SchedulerTask extends BaseModel
{
    protected $table = 'public.scheduler_tasks';
    protected $fillable = ['name', 'shortname', 'module', 'task_type', 'target', 'options', 'status', 'tenant_id'];
    protected function casts(): array { return ['options' => 'array', 'status' => 'integer']; }
}
