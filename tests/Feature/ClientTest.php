<?php

declare(strict_types=1);

use App\Models\Client;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000008_sync_clients.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('creates edits and soft deletes clients with version and tenant protection', function () {
    $this->loginUser($this->makeUser([], true));
    $payload = ['name' => 'Клиент', 'shortname' => null, 'status' => 1];
    $this->postJson('/web/clients', [...$payload, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $this->postJson('/web/clients', [...$payload, 'status' => 99])->assertUnprocessable();
    $client = $this->postJson('/web/clients', $payload)->assertCreated()->assertJsonPath('data.tenant_id', 'tenant_a')->json('data');
    $updated = $this->putJson('/web/clients/'.$client['id'], [...$payload, 'status' => 2, 'version' => $client['version']])->assertOk()->assertJsonPath('data.status', 2)->json('data');
    $this->putJson('/web/clients/'.$client['id'], [...$payload, 'version' => $client['version']])->assertConflict()->assertJsonPath('current.version', $updated['version']);
    $foreign = DB::table('clients.clients')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    $this->putJson('/web/clients/'.$foreign, [...$payload, 'version' => '0'])->assertNotFound();
    $this->deleteJson('/web/clients/'.$foreign, ['version' => '0'])->assertNotFound();
    $this->deleteJson('/web/clients/'.$client['id'], ['version' => $client['version']])->assertConflict();
    $this->deleteJson('/web/clients/'.$client['id'], ['version' => $updated['version']])->assertOk();
    expect(Client::withTrashed()->find($client['id'])->trashed())->toBeTrue();
});

it('synchronizes only tenant clients including direct SQL changes and restricts access', function () {
    $this->loginUser($this->makeUser([], true));
    $id = DB::table('clients.clients')->insertGetId(['tenant_id' => 'tenant_a']);
    DB::table('clients.clients')->insert(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    $snapshot = $this->getJson('/web/sync/clients')->assertOk()->assertJsonCount(1, 'changes')->assertJsonPath('changes.0.data.name', null)->json();
    DB::table('clients.clients')->where('id', $id)->update(['name' => 'Обновлён']);
    $this->getJson('/web/sync/clients?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.data.name', 'Обновлён');
    $this->loginUser($this->makeUser());
    $this->get('/clients/clients')->assertForbidden();
    $this->getJson('/web/sync/clients')->assertForbidden();
    $this->postJson('/web/clients', ['name' => 'Нет', 'status' => 1])->assertForbidden();
});

it('supports clients through Bearer API and preserves source rows on migration rollback', function () {
    $admin = $this->makeUser([], true);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $client = $this->withToken($token)->postJson('/api/clients', ['name' => 'API', 'status' => 1])->assertCreated()->json('data');
    $this->getJson('/api/sync/clients')->assertOk()->assertJsonCount(1, 'changes');
    $migration = require database_path('migrations/2026_09_10_000008_sync_clients.php');
    $migration->down();
    expect(Client::find($client['id'])->name)->toBe('API');
    $migration->up();
    expect(app(EntitySyncService::class)->current(Client::class, 'tenant_a', $client['id'])['name'])->toBe('API');
});
