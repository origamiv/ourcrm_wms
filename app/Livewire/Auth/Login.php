<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\AuthenticationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

final class Login extends Component
{
    public string $email = '';

    public string $password = '';
    public array $tenants = [];

    public function login(AuthenticationService $auth): void
    {
        $this->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']]);
        try {
            $user = $auth->authenticate($this->email, $this->password, request()->ip());
        } finally {
            $this->password = '';
        }
        Auth::guard('web')->login($user);
        session()->forget(['wms_tenant', 'wms_credential']);
        $this->tenants = $this->availableTenants($user);
        if (count($this->tenants) > 1) {
            session()->put('wms_pending_tenants', collect($this->tenants)->pluck('id')->map(fn ($id) => (string) $id)->all());

            return;
        }
        $tenantId = (string) ($this->tenants[0]['id'] ?? $user->tenant_id);
        session()->regenerate();
        $this->activateTenant($user, $tenantId);
        $this->redirect('/');
    }

    public function selectTenant(string $tenantId): void
    {
        $allowed = session()->get('wms_pending_tenants', []);
        abort_unless(in_array($tenantId, array_map('strval', $allowed), true), 403);
        $user = Auth::user();
        abort_unless($user, 403);
        session()->regenerate();
        $this->activateTenant($user, $tenantId);
        session()->forget('wms_pending_tenants');
        $this->redirect('/');
    }

    /** @return array<int, array{id:string,name:string,status:int}> */
    private function availableTenants($user): array
    {
        $owned = Schema::hasTable('public.tenants') ? DB::table('public.tenants')->where('owner_user_id', $user->id)->pluck('id') : collect();
        $member = DB::table('main.role_user')->where('user_id', $user->id)->where('status', 1)
            ->whereNull('deleted_at')->whereNotNull('tenant_id')->pluck('tenant_id');
        $assigned = Schema::hasTable('main.tenant_entity') ? DB::table('main.tenant_entity')->where('entity_type', $user->getMorphClass())->where('entity_id', $user->id)->pluck('tenant_id') : collect();
        $ids = $owned->merge($member)->merge($assigned)->push($user->tenant_id)->filter()->map(fn ($id) => (string) $id)->unique()->values();
        if (! Schema::hasTable('public.tenants')) {
            return $ids->map(fn ($id) => ['id' => $id, 'name' => $id, 'status' => 1])->all();
        }

        return DB::table('public.tenants')->whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'status'])
            ->map(fn ($tenant) => ['id' => (string) $tenant->id, 'name' => (string) $tenant->name, 'status' => (int) $tenant->status])->all();
    }

    private function activateTenant($user, string $tenantId): void
    {
        $user->tenant_id = $tenantId;
        session()->put('wms_tenant', $tenantId);
        session()->put('wms_credential', $user->credentialFingerprint());
    }

    public function render()
    {
        return view('auth.login')->layout('auth.layout');
    }
}
