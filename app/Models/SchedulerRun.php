<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulerRun extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'public.scheduler_runs';

    protected $fillable = ['scheduler_id', 'tenant_id', 'module', 'task_key', 'status', 'queued_at', 'started_at', 'finished_at', 'error_message', 'result'];

    protected function casts(): array
    {
        return ['queued_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'result' => 'array'];
    }

    public function scheduler()
    {
        return $this->belongsTo(Scheduler::class);
    }
}
