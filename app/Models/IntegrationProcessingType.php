<?php

declare(strict_types=1);

namespace App\Models;

final class IntegrationProcessingType extends BaseModel
{
    protected $table = 'integration.type_processing';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
