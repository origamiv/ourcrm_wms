<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'public.users';

    protected $fillable = ['name', 'last_name', 'middle_name', 'nick', 'email', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    public function credentialFingerprint(): string
    {
        return hash_hmac('sha256', $this->getAuthPassword().'|'.$this->tenant_id, config('app.key'));
    }

    protected function casts(): array
    {
        return ['password' => 'hashed', 'status' => 'integer', 'tenant_id' => 'string'];
    }
}
