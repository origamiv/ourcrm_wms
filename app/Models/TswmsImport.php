<?php

declare(strict_types=1);

namespace App\Models;

final class TswmsImport extends BaseModel
{
    protected $table = 'wms.tswms_imports';

    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'warnings' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'source_client_id' => 'integer',
        'total_stages' => 'integer',
        'completed_stages' => 'integer',
        'total_jobs' => 'integer',
        'completed_jobs' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
    ];
}
