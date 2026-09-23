<?php

declare(strict_types=1);

namespace App\Models;

final class UserFilterPreset extends BaseModel
{
    protected $table = 'main.user_filter_presets';

    protected $guarded = ['*'];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
        'user_id' => 'integer',
    ];
}
