<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportRunStage extends BaseModel
{
    protected $table = 'wms.import_run_stages';

    protected $guarded = ['id'];

    protected $casts = [
        'stage_number' => 'integer',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'total_chunks' => 'integer',
        'processed_chunks' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class, 'import_run_id');
    }
}
