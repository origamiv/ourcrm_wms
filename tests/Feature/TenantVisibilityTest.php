<?php

declare(strict_types=1);

use App\Models\GoodType;
use App\Models\Role;
use App\Services\AccessService;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000016_relate_good_cards_to_goods.php'))->up();
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->up();
    $this->admin = $this->makeUser([], true);
    $this->other = $this->makeUser(['tenant_id' => 'tenant_b'], true);
    $this->loginUser($this->admin);
    config(['wms.sync_page_size' => 100]);
});
afterEach(function () {
    DB::rollBack();
});

function shareType(int $id, ?string $tenant, string $entity = GoodType::class): int
{
    return DB::table('main.tenant_entity')->insertGetId(['entity_type' => $entity, 'entity_id' => $id, 'tenant_id' => $tenant]);
}

it('applies ownership and per-record polymorphic allowlists', function () {
    $open = DB::table('goods.type_goods')->insertGetId(['name' => 'Для всех']);
    $restricted = DB::table('goods.type_goods')->insertGetId(['name' => 'Только А']);
    $own = DB::table('goods.type_goods')->insertGetId(['name' => 'Свой', 'tenant_id' => 'tenant_a']);
    $foreign = DB::table('goods.type_goods')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    $nobody = DB::table('goods.type_goods')->insertGetId(['name' => 'Нет разрешённых']);
    shareType($restricted, 'tenant_a');
    shareType($open, 'tenant_b', App\Models\GoodUnit::class); // Same ID, different entity.
    shareType($own, 'tenant_b'); // An allowlist cannot override explicit ownership.
    shareType($foreign, 'tenant_a');
    shareType($nobody, null);

    expect(GoodType::visibleTo('tenant_a')->orderBy('id')->pluck('id')->all())->toBe([$open, $restricted, $own]);
    expect(GoodType::visibleTo('tenant_b')->orderBy('id')->pluck('id')->all())->toBe([$open, $foreign]);
    $page = $this->getJson('/web/sync/type_goods')->assertOk()->json();
    expect(array_column($page['changes'], 'id'))->toEqualCanonicalizing(array_map('strval', [$open, $restricted, $own]));
    $this->get('/goods/type_goods/'.$restricted.'/view')->assertOk();
    $this->get('/goods/type_goods/'.$foreign.'/view')->assertNotFound();
    $this->loginUser($this->other);
    $this->get('/goods/type_goods/'.$restricted.'/edit')->assertNotFound();
});

it('edits shared records only as an allowed admin and preserves NULL ownership via web and API', function () {
    $id = DB::table('goods.type_goods')->insertGetId(['name' => 'Общий']);
    shareType($id, 'tenant_a');
    $sync = app(EntitySyncService::class);
    $version = $sync->current(GoodType::class, 'tenant_a', $id)['version'];
    $payload = ['name' => 'Изменён', 'status' => 1, 'version' => $version];
    $saved = $this->putJson('/web/goods/type_goods/'.$id, $payload)->assertOk()->assertJsonPath('data.tenant_id', null)->json('data');
    expect(GoodType::find($id)->tenant_id)->toBeNull();
    $this->putJson('/web/goods/type_goods/'.$id, $payload)->assertConflict();
    $this->putJson('/web/goods/type_goods/'.$id, [...$payload, 'tenant_id' => 'tenant_a'])->assertUnprocessable();
    $this->loginUser($this->other);
    $this->putJson('/web/goods/type_goods/'.$id, $payload)->assertNotFound();
    $ordinary = $this->makeUser();
    $this->loginUser($ordinary);
    $this->putJson('/web/goods/type_goods/'.$id, $payload)->assertForbidden();
    $this->get('/goods/type_goods/'.$id.'/edit')->assertForbidden();
    $this->loginUser($this->admin);
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    auth()->forgetGuards();
    $this->withToken($token)->putJson('/api/goods/type_goods/'.$id, [...$payload, 'version' => $saved['version']])->assertOk()->assertJsonPath('data.tenant_id', null);
});

it('delivers grants revocations ownership moves and shared edits as tenant deltas', function () {
    $id = DB::table('goods.type_goods')->insertGetId(['name' => 'Общий']);
    $sync = app(EntitySyncService::class);
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', null, null);
    $b = $sync->page(GoodType::class, 'tenant_b', 'b', null, null);
    $link = shareType($id, 'tenant_a');
    $b = $sync->page(GoodType::class, 'tenant_b', 'b', $b['cursor'], null);
    expect($b['mode'])->toBe('delta');
    expect($b['changes'][0]['operation'])->toBe('remove');
    expect($b['changes'][0]['data'])->toBeNull();
    DB::table('goods.type_goods')->where('id', $id)->update(['name' => 'Новое имя']);
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', $a['cursor'], null);
    expect(end($a['changes'])['data']['name'])->toBe('Новое имя');
    DB::table('main.tenant_entity')->where('id', $link)->update(['tenant_id' => 'tenant_b']);
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', $a['cursor'], null);
    expect(end($a['changes'])['operation'])->toBe('remove');
    $b = $sync->page(GoodType::class, 'tenant_b', 'b', $b['cursor'], null);
    expect(end($b['changes'])['data']['name'])->toBe('Новое имя');
    DB::table('main.tenant_entity')->where('id', $link)->delete();
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', $a['cursor'], null);
    expect(end($a['changes'])['operation'])->toBe('upsert');
    DB::table('goods.type_goods')->where('id', $id)->update(['tenant_id' => 'tenant_b']);
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', $a['cursor'], null);
    expect(end($a['changes'])['operation'])->toBe('remove');
    DB::table('goods.type_goods')->where('id', $id)->update(['tenant_id' => null]);
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', $a['cursor'], null);
    expect(end($a['changes'])['operation'])->toBe('upsert');
    DB::table('goods.type_goods')->where('id', $id)->delete();
    $a = $sync->page(GoodType::class, 'tenant_a', 'a', $a['cursor'], null);
    expect(end($a['changes'])['operation'])->toBe('remove');
});

it('initializes new tenants and does not replay revoked payloads from old pages', function () {
    $first = DB::table('goods.type_goods')->insertGetId(['name' => 'Первый']);
    $secret = DB::table('goods.type_goods')->insertGetId(['name' => 'Секрет']);
    $sync = app(EntitySyncService::class);
    config(['wms.sync_page_size' => 1]);
    $page = $sync->page(GoodType::class, 'tenant_a', 'a', null, null);
    expect($page['continuation'])->not->toBeNull();
    shareType($secret, 'tenant_b');
    $next = $sync->page(GoodType::class, 'tenant_a', 'a', null, $page['continuation']);
    expect($next['changes'][0]['operation'])->toBe('remove');
    expect($next['changes'][0]['data'])->toBeNull();
    config(['wms.sync_page_size' => 100]);
    $new = $sync->page(GoodType::class, 'new_tenant', 'new', null, null);
    expect(array_column($new['changes'], 'id'))->toBe([(string) $first]);
    DB::statement('TRUNCATE main.tenant_entity');
    $new = $sync->page(GoodType::class, 'new_tenant', 'new', $new['cursor'], null);
    expect(collect($new['changes'])->firstWhere('id', (string) $secret)['operation'])->toBe('upsert');
});

it('allows visible shared lookup records and rejects restricted ones', function () {
    $type = DB::table('goods.type_goods')->insertGetId(['name' => 'Общий тип']);
    $payload = ['name' => 'Товар', 'level' => 0, 'status' => 1, 'type_good' => $type];
    $this->postJson('/web/goods/goods', $payload)->assertCreated();
    shareType($type, 'tenant_b');
    $this->postJson('/web/goods/goods', $payload)->assertUnprocessable()->assertJsonValidationErrors('type_good');
});

it('checks assigned admin authority independently of role catalog visibility', function () {
    $access = app(AccessService::class);
    $role = DB::table('main.role_user')->where('user_id', $this->admin->id)->value('role_id');
    expect($access->isAdmin($this->admin))->toBeTrue();
    shareType($role, 'tenant_b', Role::class);
    expect(Role::visibleTo('tenant_a')->whereKey($role)->exists())->toBeFalse();
    expect($access->isAdmin($this->admin))->toBeTrue();
    DB::table('main.tenant_entity')->delete();
    $assignment = DB::table('main.role_user')->where('user_id', $this->admin->id)->value('id');
    DB::table('main.role_user')->where('id', $assignment)->update(['tenant_id' => null]);
    shareType($assignment, 'tenant_b', 'App\\Models\\RoleUser');
    expect($access->isAdmin($this->admin))->toBeFalse();
    DB::table('main.tenant_entity')->delete();
    expect($access->isAdmin($this->admin))->toBeTrue();
});

it('uses tenant versions for shared user reads and admin password changes', function () {
    $user = $this->makeUser(['tenant_id' => null]);
    shareType($user->id, 'tenant_a', App\Models\User::class);
    $this->loginUser($this->admin);
    $row = $this->getJson('/web/users/'.$user->id)->assertOk()->assertJsonPath('data.tenant_id', null)->assertJsonMissingPath('data.password')->json('data');
    $this->postJson('/web/users/'.$user->id.'/password', ['version' => $row['version'], 'password' => 'Changed_password_456', 'password_confirmation' => 'Changed_password_456'])->assertOk()->assertJsonPath('data.tenant_id', null);
    expect($user->fresh()->tenant_id)->toBeNull();
    $this->loginUser($this->other);
    $this->getJson('/web/users/'.$user->id)->assertNotFound();
});

it('detects simultaneous shared edits from different organizations', function () {
    $id = DB::table('goods.type_goods')->insertGetId(['name' => 'Общий']);
    $sync = app(EntitySyncService::class);
    $a = $sync->current(GoodType::class, 'tenant_a', $id);
    $b = $sync->current(GoodType::class, 'tenant_b', $id);
    $this->loginUser($this->other);
    $this->putJson('/web/goods/type_goods/'.$id, ['name' => 'Изменил Б', 'status' => 1, 'version' => $b['version']])->assertOk()->assertJsonPath('data.tenant_id', null);
    $this->loginUser($this->admin);
    $this->putJson('/web/goods/type_goods/'.$id, ['name' => 'Изменил А', 'status' => 1, 'version' => $a['version']])->assertConflict()->assertJsonPath('current.name', 'Изменил Б');
});

it('refreshes both entities when an assignment changes its polymorphic target', function () {
    $type = DB::table('goods.type_goods')->insertGetId(['name' => 'Тип']);
    $unit = DB::table('goods.unit_goods')->insertGetId(['name' => 'Единица']);
    $assignment = shareType($type, 'tenant_b');
    $sync = app(EntitySyncService::class);
    $types = $sync->page(GoodType::class, 'tenant_a', 'a', null, null);
    $units = $sync->page(App\Models\GoodUnit::class, 'tenant_a', 'a', null, null);
    DB::table('main.tenant_entity')->where('id', $assignment)->update(['entity_type' => App\Models\GoodUnit::class, 'entity_id' => $unit]);
    $types = $sync->page(GoodType::class, 'tenant_a', 'a', $types['cursor'], null);
    $units = $sync->page(App\Models\GoodUnit::class, 'tenant_a', 'a', $units['cursor'], null);
    expect($types['changes'][0]['operation'])->toBe('upsert');
    expect($units['changes'][0]['operation'])->toBe('remove');
});

it('keeps menus and section access for an explicitly assigned admin role owned by another tenant', function () {
    $role = DB::table('main.role_user')->where('user_id', $this->admin->id)->value('role_id');
    DB::table('main.roles')->where('id', $role)->update(['tenant_id' => 'tenant_b']);
    expect(Role::visibleTo('tenant_a')->whereKey($role)->exists())->toBeFalse();
    expect(app(AccessService::class)->isAdmin($this->admin))->toBeTrue();
    $this->get('/')->assertOk()->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page->where('auth.is_admin', true));
    $this->get('/main/users')->assertOk();
    $this->get('/clients/clients')->assertOk();
    $this->get('/goods/goods')->assertOk();
    $this->getJson('/web/sync/type_goods')->assertOk();
    DB::table('main.roles')->where('id', $role)->update(['status' => 2]);
    expect(app(AccessService::class)->isAdmin($this->admin))->toBeFalse();
    $this->get('/main/users')->assertForbidden();
});
