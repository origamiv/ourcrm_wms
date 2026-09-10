<?php

declare(strict_types=1);

namespace App\Models;

final class IntegrationHookType extends BaseModel
{
    protected $table = 'integration.type_hook';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
