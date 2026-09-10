<?php

declare(strict_types=1);

namespace App\Models;

final class IntegrationRule extends BaseModel
{
    protected $table = 'integration.rules';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'params' => 'array'];

    public function typeProcessing_obj(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(IntegrationProcessingType::class, 'type_processing_id');
    }
}
