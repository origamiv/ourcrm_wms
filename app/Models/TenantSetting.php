<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantSetting extends Model
{
    protected $table = 'main.tenant_settings';

    protected $guarded = ['*'];

    protected $casts = ['value' => 'array'];
}
