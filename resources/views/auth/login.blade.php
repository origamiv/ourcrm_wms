<div class="login-screen">
    <div class="login-panel">
        <div class="brand"><img class="brand-logo" src="/design/crm/logo.png" alt=""><span>WMS<span class="brand-caption">Управление складом</span></span></div>
        <h1>Вход в систему</h1><p class="muted">Введите данные вашей учётной записи</p>
        <form wire:submit="login" class="login-form" x-data="{ ready: false }" x-init="$nextTick(() => ready = true)">
            <label>Email<input type="email" wire:model="email" required autocomplete="username" autofocus></label>
            <label>Пароль<input type="password" wire:model="password" required autocomplete="current-password"></label>
            @error('email')<p class="notice error" role="alert">{{ $message }}</p>@enderror
            @error('password')<p class="notice error" role="alert">{{ $message }}</p>@enderror
            <button class="primary" type="submit" disabled x-bind:disabled="!ready" wire:loading.attr="disabled"><span wire:loading.remove>Войти</span><span wire:loading>Входим…</span></button>
        </form>
        <p class="login-note">Для доступа обратитесь к администратору вашей организации.</p>
    </div>
</div>
