<?php

declare(strict_types=1);

namespace App\Models;

final class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    protected $table = 'wms.personal_access_tokens';

    protected $hidden = ['token', 'credential_fingerprint'];
}
