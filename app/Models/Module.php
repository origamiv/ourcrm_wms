<?php

declare(strict_types=1);

namespace App\Models;

final class Module extends BaseModel
{
    protected $table = 'main.modules';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
