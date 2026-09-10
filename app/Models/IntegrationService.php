<?php

declare(strict_types=1);

namespace App\Models;

final class IntegrationService extends BaseModel
{
    protected $table = 'integration.services';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
