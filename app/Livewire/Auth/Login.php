<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\AuthenticationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function login(AuthenticationService $auth): void
    {
        $this->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']]);
        try {
            $user = $auth->authenticate($this->email, $this->password, request()->ip());
        } finally {
            $this->password = '';
        }
        Auth::guard('web')->login($user);
        session()->regenerate();
        session()->put('wms_credential', $user->credentialFingerprint());
        $this->redirect('/');
    }

    public function render()
    {
        return view('auth.login')->layout('auth.layout');
    }
}
