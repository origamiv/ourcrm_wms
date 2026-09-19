<?php

declare(strict_types=1);

namespace App\Models;

class Scheduler extends BaseModel
{
    protected $table = 'public.scheduler';

    protected $fillable = ['name', 'module', 'tenant_id', 'task_key', 'task_type', 'params', 'schedule', 'status', 'next_run_at', 'last_run_at'];

    protected function casts(): array
    {
        return ['params' => 'array', 'schedule' => 'array', 'status' => 'integer', 'next_run_at' => 'datetime', 'last_run_at' => 'datetime'];
    }

    public function runs()
    {
        return $this->hasMany(SchedulerRun::class);
    }
}
