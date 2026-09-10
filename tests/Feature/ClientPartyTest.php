<?php

declare(strict_types=1);

use App\Models\ClientCompany;
use App\Models\ClientIndividual;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000007_sync_companies.php'))->up();
    (require database_path('migrations/2026_09_10_000008_sync_clients.php'))->up();
    (require database_path('migrations/2026_09_10_000010_sync_client_parties.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('creates edits and deletes client parties with valid tenant relationships and versions', function () {
    $admin = $this->makeUser([], true);
    $foreignUser = $this->makeUser(['tenant_id' => 'tenant_b']);
    $this->loginUser($admin);
    $client = $this->postJson('/web/clients', ['name' => 'Клиент', 'status' => 1])->assertCreated()->json('data');
    $foreignClient = DB::table('clients.clients')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    foreach (['companies', 'individuals'] as $party) {
        $payload = ['name' => 'Запись', 'shortname' => 'Запись', 'client_id' => $client['id'], 'status' => 1];
        $this->postJson('/web/clients/'.$party, [...$payload, 'client_id' => $foreignClient])->assertUnprocessable();
        $this->postJson('/web/clients/'.$party, [...$payload, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
        $row = $this->postJson('/web/clients/'.$party, $payload)->assertCreated()->json('data');
        $this->get('/clients/'.$party.'/'.$row['id'].'/edit')->assertOk();
        $this->deleteJson('/web/clients/'.$client['id'], ['version' => $client['version']])->assertUnprocessable();
        $updated = $this->putJson('/web/clients/'.$party.'/'.$row['id'], [...$payload, 'name' => 'Обновлено', 'status' => 2, 'version' => $row['version']])->assertOk()->json('data');
        $this->putJson('/web/clients/'.$party.'/'.$row['id'], [...$payload, 'version' => $row['version']])->assertConflict();
        $this->deleteJson('/web/clients/'.$party.'/'.$row['id'], ['version' => $updated['version']])->assertOk();
    }
    $this->postJson('/web/clients/individuals', ['name' => 'Человек', 'status' => 1, 'manager_id' => $foreignUser->id])->assertUnprocessable();
    $this->postJson('/web/clients/individuals', ['name' => 'Человек', 'status' => 1, 'birthday' => '2025-02-31'])->assertUnprocessable();
    $this->deleteJson('/web/clients/'.$client['id'], ['version' => $client['version']])->assertOk();
});

it('isolates client parties from main companies and other tenants and journals only safe JSON', function () {
    $this->loginUser($this->makeUser([], true));
    $id = DB::table('clients.companies')->insertGetId(['name' => 'Юрлицо', 'shortname' => 'Юрлицо', 'tenant_id' => 'tenant_a', 'src' => json_encode(['private_key' => 'private', 'telegram' => '@old']), 'company_src' => json_encode(['private_data' => true])]);
    $foreign = DB::table('clients.companies')->insertGetId(['name' => 'Чужая', 'shortname' => 'Чужая', 'tenant_id' => 'tenant_b']);
    $snapshot = $this->getJson('/web/sync/client_companies')->assertOk()->assertJsonCount(1, 'changes')->json();
    expect($snapshot['changes'][0]['data'])->not->toHaveKey('company_src');
    expect($snapshot['changes'][0]['data']['src'])->not->toHaveKey('private_key');
    $this->get('/clients/companies/'.$foreign.'/view')->assertNotFound();
    $this->putJson('/web/clients/companies/'.$foreign, ['name' => 'Нет', 'shortname' => 'Нет', 'status' => 1, 'version' => '0'])->assertNotFound();
    $this->putJson('/web/clients/companies/'.$id, ['name' => 'Юрлицо', 'shortname' => 'Юрлицо', 'status' => 1, 'version' => $snapshot['changes'][0]['version'], 'src' => ['telegram' => '@new']])->assertOk();
    expect(ClientCompany::find($id)->src)->toMatchArray(['private_key' => 'private', 'telegram' => '@new']);
    DB::table('clients.companies')->where('id', $id)->update(['name' => 'SQL изменение']);
    $this->getJson('/web/sync/client_companies?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonFragment(['name' => 'SQL изменение']);
    $migration = require database_path('migrations/2026_09_10_000010_sync_client_parties.php');
    $migration->down();
    expect(ClientCompany::find($id)->name)->toBe('SQL изменение');
    $migration->up();
    expect(app(EntitySyncService::class)->current(ClientCompany::class, 'tenant_a', $id)['name'])->toBe('SQL изменение');
    $this->loginUser($this->makeUser());
    $this->getJson('/web/sync/client_individuals')->assertForbidden();
    $this->get('/clients/individuals')->assertForbidden();
});

it('supports individual dates and documents through bearer API', function () {
    $admin = $this->makeUser([], true);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $row = $this->withToken($token)->postJson('/api/clients/individuals', ['name' => 'Иван Тестовый', 'firstname' => 'Иван', 'birthday' => '1990-01-02', 'passport_seria' => '0000', 'passport_number' => '000001', 'manager_id' => $admin->id, 'status' => 1])->assertCreated()->assertJsonPath('data.birthday', '1990-01-02')->json('data');
    $this->getJson('/api/sync/client_individuals')->assertOk()->assertJsonCount(1, 'changes');
    $this->deleteJson('/api/clients/individuals/'.$row['id'], ['version' => $row['version']])->assertOk();
    expect(ClientIndividual::withTrashed()->find($row['id'])->trashed())->toBeTrue();
});

it('locks creation updates deletion and direct pages to the client in scoped routes', function () {
    $this->loginUser($this->makeUser([], true));
    $first = DB::table('clients.clients')->insertGetId(['name' => 'Первый', 'tenant_id' => 'tenant_a']);
    $second = DB::table('clients.clients')->insertGetId(['name' => 'Второй', 'tenant_id' => 'tenant_a']);
    $foreign = DB::table('clients.clients')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    foreach (['companies', 'individuals'] as $party) {
        $payload = ['name' => 'Запись', 'shortname' => 'Запись', 'status' => 1];
        $base = '/web/clients/'.$first.'/'.$party;
        $row = $this->postJson($base, $payload)->assertCreated()->assertJsonPath('data.client_id', $first)->json('data');
        $this->postJson($base, [...$payload, 'client_id' => $second])->assertUnprocessable();
        $this->postJson($base, [...$payload, 'client_id' => null])->assertUnprocessable();
        $this->postJson('/web/clients/'.$foreign.'/'.$party, $payload)->assertNotFound();
        $this->get('/clients/'.$party.'?client_id='.$first)->assertOk();
        $this->get('/clients/'.$party.'?client_id='.$foreign)->assertNotFound();
        $this->get('/clients/'.$party.'/'.$row['id'].'/view?client_id='.$first)->assertOk();
        $this->get('/clients/'.$party.'/'.$row['id'].'/edit?client_id='.$second)->assertNotFound();
        $this->putJson('/web/clients/'.$second.'/'.$party.'/'.$row['id'], [...$payload, 'version' => $row['version']])->assertNotFound();
        $this->putJson($base.'/'.$row['id'], [...$payload, 'client_id' => $second, 'version' => $row['version']])->assertUnprocessable();
        $updated = $this->putJson($base.'/'.$row['id'], [...$payload, 'name' => 'Обновлено', 'version' => $row['version']])->assertOk()->assertJsonPath('data.client_id', $first)->json('data');
        $this->deleteJson('/web/clients/'.$second.'/'.$party.'/'.$row['id'], ['version' => $updated['version']])->assertNotFound();
        $this->deleteJson($base.'/'.$row['id'], ['version' => $updated['version']])->assertOk();
    }
});

it('enforces client scope through the bearer API as well', function () {
    $admin = $this->makeUser([], true);
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Клиент', 'tenant_id' => 'tenant_a']);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    foreach (['companies', 'individuals'] as $party) {
        $this->withToken($token)->postJson('/api/clients/'.$client.'/'.$party, ['name' => 'API', 'shortname' => 'API', 'status' => 1])->assertCreated()->assertJsonPath('data.client_id', $client);
    }
});
