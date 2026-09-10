<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class AuthenticationService
{
    public function authenticate(string $email, string $password, string $ip): User
    {
        $key = 'wms_login:'.hash('sha256', mb_strtolower(trim($email)).'|'.$ip);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Слишком много попыток. Повторите через минуту.']);
        }
        RateLimiter::hit($key, 60);
        $users = User::withTrashed()->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])->limit(2)->get();
        $user = $users->count() === 1 ? $users->first() : null;
        $valid = false;
        if ($user && $user->password) {
            try {
                $valid = Hash::check($password, $user->password);
            } catch (RuntimeException) {
                $valid = false;
            }
        }
        if (! $valid || ! app(AccessService::class)->active($user)) {
            throw ValidationException::withMessages(['email' => 'Вход недоступен. Проверьте данные и состояние учётной записи.']);
        }
        RateLimiter::clear($key);

        return $user;
    }
}
