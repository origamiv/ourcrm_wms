<?php

declare(strict_types=1);

namespace App\Models;

final class SharedService extends BaseModel
{
    protected $table = 'shared.services';

    protected $guarded = ['*'];

    protected $casts = [
        'source_id' => 'integer',
        'status' => 'integer',
        'category_id' => 'integer',
        'options' => 'array',
        'cnt' => 'integer',
        'tags' => 'array',
    ];
}
