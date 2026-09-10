<?php

declare(strict_types=1);

use App\Models\Kiz;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000021_create_kind_kiz.php'))->up();
    (require database_path('migrations/2026_09_10_000022_create_kizes.php'))->up();
    $this->admin = $this->makeUser([], true);
    $this->loginUser($this->admin);
});
afterEach(function () {
    DB::rollBack();
});

it('creates and edits markings with client goods kinds and dates then syncs deletion', function () {
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Клиент', 'tenant_id' => 'tenant_a']);
    $good = DB::table('goods.goods')->insertGetId(['name' => 'Товар', 'goodcard_id' => 0, 'tenant_id' => 'tenant_a']);
    $kind = DB::table('goods.kind_kiz')->value('id');
    $payload = ['code' => '001234', 'client_id' => $client, 'good_id' => $good, 'kind_kiz_id' => $kind, 'entranced_at' => '2026-09-10T14:25', 'leaving_at' => null, 'printed_at' => '2026-09-10T13:00'];
    $row = $this->postJson('/web/goods/kizes', $payload)->assertCreated()->assertJsonPath('data.code', '001234')->json('data');
    expect((string) Kiz::find($row['id'])->client->id)->toBe((string) $client);
    $this->get('/goods/kizes/'.$row['id'].'/edit')->assertOk();
    $cursor = $this->getJson('/web/sync/kizes')->assertOk()->assertJsonCount(1, 'changes')->json('cursor');
    $updated = $this->putJson('/web/goods/kizes/'.$row['id'], [...$payload, 'code' => '001235', 'version' => $row['version']])->assertOk()->json('data');
    $this->putJson('/web/goods/kizes/'.$row['id'], [...$payload, 'version' => $row['version']])->assertConflict();
    $this->getJson('/web/sync/kizes?cursor='.urlencode($cursor))->assertOk()->assertJsonPath('changes.0.data.code', '001235');
    $this->deleteJson('/web/goods/kizes/'.$row['id'], ['version' => $updated['version']])->assertOk();
    expect(Kiz::withTrashed()->find($row['id'])->trashed())->toBeTrue();
});

it('rejects foreign relationships tenant overrides invalid dates and unauthorized writes', function () {
    foreach (['client_id' => 'clients.clients', 'good_id' => 'goods.goods', 'kind_kiz_id' => 'goods.kind_kiz'] as $field => $table) {
        $id = DB::table($table)->insertGetId(['name' => 'Чужая запись', 'tenant_id' => 'tenant_b', ...($field === 'good_id' ? ['goodcard_id' => 0] : [])]);
        $this->postJson('/web/goods/kizes', ['code' => '001', $field => $id])->assertUnprocessable()->assertJsonValidationErrors($field);
    }
    $this->postJson('/web/goods/kizes', ['tenant_id' => 'tenant_b'])->assertUnprocessable();
    $this->postJson('/web/goods/kizes', ['printed_at' => 'не дата'])->assertUnprocessable();
    $row = $this->postJson('/web/goods/kizes', ['code' => '001'])->assertCreated()->json('data');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->getJson('/web/sync/kizes')->assertOk()->assertJsonCount(0, 'changes');
    $this->get('/goods/kizes/'.$row['id'].'/view')->assertNotFound();
    $this->putJson('/web/goods/kizes/'.$row['id'], ['code' => '002', 'version' => $row['version']])->assertNotFound();
    $this->loginUser($this->makeUser());
    $this->postJson('/web/goods/kizes', ['code' => '001'])->assertForbidden();
});

it('serves marking creation through bearer API', function () {
    auth()->forgetGuards();
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token)->postJson('/api/goods/kizes', ['code' => 'API-001'])->assertCreated()->assertJsonPath('data.code', 'API-001');
});
