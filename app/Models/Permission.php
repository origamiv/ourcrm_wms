<?php

declare(strict_types=1);

namespace App\Models;

final class Permission extends BaseModel
{
    protected $table = 'main.permissions';

    protected $guarded = ['*'];

    protected $casts = ['system' => 'boolean', 'status' => 'integer'];
}
