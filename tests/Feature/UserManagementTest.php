<?php

declare(strict_types=1);
use App\Livewire\Auth\Login;
use App\Services\AuthenticationService;
use App\Services\UserSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->setupPostgres();
});
afterEach(function () {
    DB::rollBack();
});
it('authenticates using Livewire and clears the password from component state', function () {
    $user = $this->makeUser(['email' => 'admin@example.test'], true);
    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'Test_password_123')->call('login')->assertSet('password', '')->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    $this->get('/users')->assertOk();
    $this->postJson('/logout')->assertOk();
    $this->assertGuest();
});
it('rejects pending blocked busy deleted tenantless and ambiguous accounts', function () {
    foreach ([0, 2, 3] as $status) {
        $u = $this->makeUser(['status' => $status]);
        expect(fn () => app(AuthenticationService::class)->authenticate($u->email, 'Test_password_123', '127.0.0.1'))->toThrow(ValidationException::class);
    }
    foreach ([['tenant_id' => null], ['deleted_at' => now()]] as $fields) {
        $u = $this->makeUser($fields);
        expect(fn () => app(AuthenticationService::class)->authenticate($u->email, 'Test_password_123', '127.0.0.1'))->toThrow(ValidationException::class);
    }
    $this->makeUser(['email' => 'duplicate@example.test']);
    $this->makeUser(['email' => 'duplicate@example.test', 'tenant_id' => 'tenant_b']);
    expect(fn () => app(AuthenticationService::class)->authenticate('duplicate@example.test', 'Test_password_123', '127.0.0.1'))->toThrow(ValidationException::class);
});
it('isolates tenants and restricts administration', function () {
    $admin = $this->makeUser([], true);
    $foreign = $this->makeUser(['tenant_id' => 'tenant_b']);
    $this->loginUser($admin);
    $this->getJson('/web/users/'.$foreign->id)->assertNotFound();
    $this->getJson('/web/users/sync')->assertOk()->assertJsonMissing(['id' => (string) $foreign->id]);
    $this->loginUser($this->makeUser());
    $this->getJson('/web/users/sync')->assertForbidden();
});
it('creates updates conflicts blocks restores and changes passwords', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $payload = ['name' => 'Мария', 'email' => 'new@example.test', 'password' => 'Long_password_123', 'password_confirmation' => 'Long_password_123'];
    $this->postJson('/web/users', [...$payload, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $row = $this->postJson('/web/users', $payload)->assertCreated()->assertJsonPath('data.status', 0)->json('data');
    expect($row)->not->toHaveKeys(['password', 'remember_token']);
    $id = $row['id'];
    $updated = $this->putJson('/web/users/'.$id, ['name' => 'Мария новая', 'email' => $row['email'], 'version' => $row['version']])->assertOk()->json('data');
    $this->putJson('/web/users/'.$id, ['name' => 'Старая', 'email' => $row['email'], 'version' => $row['version']])->assertConflict()->assertJsonPath('current.name', 'Мария новая');
    foreach (['activate' => 1, 'block' => 2, 'delete' => 2, 'restore' => 0] as $action => $status) {
        $updated = $this->postJson("/web/users/$id/$action", ['version' => $updated['version']])->assertOk()->assertJsonPath('data.status', $status)->json('data');
    }
    $this->postJson("/web/users/$id/password", ['version' => $updated['version'], 'password' => 'Changed_password_123', 'password_confirmation' => 'Changed_password_123'])->assertOk();
    $own = app(UserSyncService::class)->current($admin->id);
    $this->postJson("/web/users/$admin->id/delete", ['version' => $own['version']])->assertUnprocessable();
});
it('uses the same policy for API tokens and revokes obsolete credentials', function () {
    $admin = $this->makeUser(['email' => 'api@example.test'], true);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token)->getJson('/api/users')->assertOk();
    DB::table('public.users')->where('id', $admin->id)->update(['password' => password_hash('Other_password_123', PASSWORD_BCRYPT)]);
    auth()->forgetGuards();
    $this->withToken($token)->getJson('/api/users')->assertUnauthorized();
});
it('rejects revoked administrator roles and passwords for sessions', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    DB::table('main.role_user')->where('user_id', $admin->id)->update(['status' => 2]);
    $this->getJson('/web/users/sync')->assertForbidden();
    $original = $admin->credentialFingerprint();
    $admin->password = 'Different_password_123';
    $admin->save();
    $this->actingAs($admin)->withSession(['wms_credential' => $original])->getJson('/web/users/sync')->assertUnauthorized();
});
it('protects documentation and documents token authentication', function () {
    $this->get('/docs/api')->assertRedirect('/login');
    $this->loginUser($this->makeUser([], true));
    $this->get('/docs/api')->assertOk();
});

it('does not restore active accounts or permit changing browser identity', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $version = app(UserSyncService::class)->current($admin->id)['version'];
    $this->postJson("/web/users/$admin->id/restore", ['version' => $version])->assertUnprocessable();
    $this->withHeaders(['X-WMS-User' => '9999', 'X-WMS-Tenant' => 'tenant_a'])->getJson('/web/users/sync')->assertUnauthorized();
});
