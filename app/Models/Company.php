<?php

declare(strict_types=1);

namespace App\Models;

final class Company extends BaseModel
{
    protected $table = 'main.companies';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'src' => 'array'];
}
