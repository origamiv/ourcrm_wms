<?php

declare(strict_types=1);

namespace App\Models;

final class PermissionRole extends BaseModel
{
    protected $table = 'main.permission_role';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
