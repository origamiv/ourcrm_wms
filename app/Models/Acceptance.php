<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Acceptance extends BaseModel
{
    protected $table = 'wms.acceptances';
    protected $guarded = ['*'];
    protected $casts = [
        'client_id' => 'integer', 'warehouse_id' => 'integer', 'task_id' => 'integer',
        'plan_count' => 'integer', 'fact_count' => 'integer', 'progress' => 'integer',
        'type_acceptance_id' => 'integer', 'status' => 'integer',
        'started_at' => 'datetime', 'finished_at' => 'datetime',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function task(): BelongsTo { return $this->belongsTo(Task::class); }
    public function typeAcceptance(): BelongsTo { return $this->belongsTo(TypeAcceptance::class, 'type_acceptance_id'); }
}
