<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_11_000033_sync_user_roles.php'))->up();
});

afterEach(function () {
    DB::rollBack();
});

it('показывает роли пользователя и назначает их с проверкой версии', function () {
    $admin = $this->makeUser([], true);
    $user = $this->makeUser(['name' => 'Сотрудник']);
    $roleA = DB::table('main.roles')->insertGetId(['name' => 'Кладовщик', 'slug' => 'warehouse', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $roleB = DB::table('main.roles')->insertGetId(['name' => 'Менеджер', 'slug' => 'manager', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $this->loginUser($admin);

    $current = $this->getJson('/web/users/'.$user->id)->assertOk()->json('data');
    expect($current['roles'])->toBe([]);
    $updated = $this->postJson('/web/users/'.$user->id.'/roles', [
        'role_ids' => [$roleA, $roleB],
        'version' => $current['version'],
    ])->assertOk()->json('data');
    expect(collect($updated['roles'])->pluck('id')->all())->toEqualCanonicalizing([(string) $roleA, (string) $roleB]);
    $this->postJson('/web/users/'.$user->id.'/roles', ['role_ids' => [], 'version' => $current['version']])->assertConflict();

    $this->postJson('/web/users/'.$user->id.'/roles', ['role_ids' => [$roleA], 'version' => $updated['version']])->assertOk();
    expect(DB::table('main.role_user')->where('user_id', $user->id)->where('role_id', $roleB)->whereNotNull('deleted_at')->exists())->toBeTrue();
});

it('запрещает недоступные роли и снятие admin с собственной учётной записи', function () {
    $admin = $this->makeUser([], true);
    $foreign = DB::table('main.roles')->insertGetId(['name' => 'Чужая', 'slug' => 'foreign', 'status' => 1, 'tenant_id' => 'tenant_b']);
    $this->loginUser($admin);
    $version = $this->getJson('/web/users/'.$admin->id)->json('data.version');
    $this->postJson('/web/users/'.$admin->id.'/roles', ['role_ids' => [$foreign], 'version' => $version])->assertUnprocessable();
    $this->postJson('/web/users/'.$admin->id.'/roles', ['role_ids' => [], 'version' => $version])->assertUnprocessable();
    $this->postJson('/web/users/'.$admin->id.'/roles', ['role_ids' => ['bad'], 'version' => $version])->assertUnprocessable();
});

it('не показывает роли из другой организации', function () {
    $admin = $this->makeUser([], true);
    $user = $this->makeUser();
    $foreign = DB::table('main.roles')->insertGetId(['name' => 'Чужая', 'slug' => 'foreign', 'status' => 1, 'tenant_id' => 'tenant_b']);
    DB::table('main.role_user')->insert(['role_id' => $foreign, 'user_id' => $user->id, 'tenant_id' => 'tenant_a', 'status' => 1]);
    $this->loginUser($admin);
    expect($this->getJson('/web/users/'.$user->id)->json('data.roles'))->toBe([]);
});
