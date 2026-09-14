<div class="login-screen">
    <div class="login-panel {{ count($tenants) > 1 ? 'login-panel-tenants' : '' }}">
        <div class="brand"><img class="brand-logo" src="/design/crm/logo.png" alt=""><span>WMS<span class="brand-caption">Управление складом</span></span></div>
        <h1>Вход в систему</h1><p class="muted">Введите данные вашей учётной записи</p>
        @if(count($tenants) > 1)
        <div class="tenant-choice">
            <h2>Выберите компанию</h2>
            <p class="muted">В какой организации открыть личный кабинет?</p>
            <div class="tenant-cards">
                @foreach($tenants as $tenant)
                    <button type="button" class="tenant-card" wire:click="selectTenant('{{ $tenant['id'] }}')">
                        <span class="tenant-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 20V9.5L12 4l8 5.5V20M8 20v-6h8v6M9 10h.01M12 10h.01M15 10h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="tenant-card-text"><strong>{{ $tenant['name'] }}</strong><small class="tenant-card-status {{ (int) $tenant['status'] === 1 ? 'is-active' : 'is-blocked' }}"><i></i>{{ (int) $tenant['status'] === 1 ? 'Доступ открыт' : 'Аккаунт заблокирован' }}</small></span>
                        <span class="tenant-card-arrow" aria-hidden="true">→</span>
                    </button>
                @endforeach
            </div>
        </div>
        @else
        <form wire:submit="login" class="login-form" x-data="{ ready: false }" x-init="$nextTick(() => ready = true)">
            <label>Email<input type="email" wire:model="email" required autocomplete="username" autofocus></label>
            <label>Пароль<input type="password" wire:model="password" required autocomplete="current-password"></label>
            @error('email')<p class="notice error" role="alert">{{ $message }}</p>@enderror
            @error('password')<p class="notice error" role="alert">{{ $message }}</p>@enderror
            <button class="primary" type="submit" disabled x-bind:disabled="!ready" wire:loading.attr="disabled"><span wire:loading.remove>Войти</span><span wire:loading>Входим…</span></button>
        </form>
        @endif
        <p class="login-note">Для доступа обратитесь к администратору вашей организации.</p>
    </div>
</div>
