<?php

declare(strict_types=1);

namespace App\Models;

final class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    protected $table = 'public.personal_access_tokens';

    protected $hidden = ['token', 'name'];

    public function credentialFingerprint(): ?string
    {
        return str_starts_with($this->name, 'wms:') ? mb_substr($this->name, 4) : null;
    }
}
