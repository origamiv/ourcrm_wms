<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends BaseModel
{
    protected $table = 'wms.tasks';
    protected $guarded = ['*'];
    protected $casts = ['planned_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'charged_at' => 'datetime', 'confirmed_at' => 'datetime', 'charged_sum' => 'decimal:2', 'src' => 'array', 'fact_count' => 'integer', 'status_id' => 'integer', 'priority_id' => 'integer', 'task_type_id' => 'integer', 'user_id' => 'integer', 'created_by_user_id' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
