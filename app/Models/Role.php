<?php

declare(strict_types=1);

namespace App\Models;

final class Role extends BaseModel
{
    protected $table = 'main.roles';

    protected $guarded = ['*'];

    protected $casts = ['system' => 'boolean', 'status' => 'integer', 'tags' => 'array'];
}
