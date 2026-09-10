<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\CompanyContact;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000007_sync_companies.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('creates and edits tenant companies and contacts and protects relationships on deletion', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $company = $this->postJson('/web/companies', ['name' => 'Склад', 'shortname' => 'Склад', 'status' => 1])->assertCreated()->json('data');
    $this->postJson('/web/company_contacts', ['name' => 'Иван', 'shortname' => 'Иван', 'company_id' => $company['id'], 'status' => 1, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $foreign = DB::table('main.companies')->insertGetId(['name' => 'Чужая', 'shortname' => 'Чужая', 'tenant_id' => 'tenant_b']);
    $this->postJson('/web/company_contacts', ['name' => 'Иван', 'shortname' => 'Иван', 'company_id' => $foreign, 'status' => 1])->assertUnprocessable();
    $contact = $this->postJson('/web/company_contacts', ['name' => 'Иван', 'shortname' => 'Иван', 'company_id' => $company['id'], 'val' => 'test@example.test', 'status' => 1])->assertCreated()->json('data');
    $updated = $this->putJson('/web/companies/'.$company['id'], ['name' => 'Новый склад', 'shortname' => 'Склад', 'status' => 2, 'version' => $company['version']])->assertOk()->assertJsonPath('data.status', 2)->json('data');
    $this->deleteJson('/web/companies/'.$company['id'], ['version' => $company['version']])->assertConflict();
    $this->deleteJson('/web/companies/'.$company['id'], ['version' => $updated['version']])->assertUnprocessable();
    $this->deleteJson('/web/company_contacts/'.$contact['id'], ['version' => $contact['version']])->assertOk();
    $this->deleteJson('/web/companies/'.$company['id'], ['version' => $updated['version']])->assertOk();
    expect(Company::withTrashed()->find($company['id'])->trashed())->toBeTrue();
    expect(CompanyContact::withTrashed()->find($contact['id'])->trashed())->toBeTrue();
    $this->deleteJson('/web/companies/'.$foreign, ['version' => '0'])->assertNotFound();
});

it('synchronizes both directories with tenant isolation and captures direct updates', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $company = DB::table('main.companies')->insertGetId(['name' => 'Склад', 'shortname' => 'Склад', 'tenant_id' => 'tenant_a']);
    DB::table('main.companies')->insert(['name' => 'Чужая', 'shortname' => 'Чужая', 'tenant_id' => 'tenant_b']);
    $snapshot = $this->getJson('/web/sync/companies')->assertOk()->assertJsonCount(1, 'changes')->json();
    DB::table('main.companies')->where('id', $company)->update(['name' => 'Обновлено']);
    $this->getJson('/web/sync/companies?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.data.name', 'Обновлено');
    $this->loginUser($this->makeUser());
    $this->getJson('/web/sync/companies')->assertForbidden();
    $this->postJson('/web/companies', ['name' => 'Нет', 'shortname' => 'Нет', 'status' => 1])->assertForbidden();
});

it('merges only approved src keys and exposes safe JSON through sync and Bearer API', function () {
    $admin = $this->makeUser([], true);
    $company = DB::table('main.companies')->insertGetId(['name' => 'Склад', 'shortname' => 'Склад', 'status' => 1, 'tenant_id' => 'tenant_a', 'src' => json_encode(['private_key' => 'keep_private', 'telegram' => '@old'])]);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token);
    $sync = app(EntitySyncService::class);
    $current = $sync->current(Company::class, 'tenant_a', $company);
    expect($current['src'])->not->toHaveKey('private_key');
    $this->putJson('/api/companies/'.$company, ['name' => 'Склад', 'shortname' => 'Склад', 'status' => 1, 'version' => $current['version'], 'src' => ['telegram' => '@new', 'is_client' => true]])->assertOk()->assertJsonPath('data.src.telegram', '@new')->assertJsonMissing(['private_key' => 'keep_private']);
    expect(Company::find($company)->src)->toMatchArray(['private_key' => 'keep_private', 'telegram' => '@new', 'is_client' => true]);
    $this->postJson('/api/companies', ['name' => 'API', 'shortname' => 'API', 'status' => 1, 'src' => ['private_key' => 'injection']])->assertUnprocessable();
    $this->postJson('/api/companies', ['name' => 'API', 'shortname' => 'API', 'status' => 1])->assertCreated();
    $this->getJson('/api/sync/company_contacts')->assertOk();
});

it('bootstraps existing companies safely and rolls back without changing source records', function () {
    $company = DB::table('main.companies')->insertGetId(['name' => 'Существующая', 'shortname' => 'Склад', 'tenant_id' => 'tenant_a', 'src' => json_encode(['private_key' => 'keep_private', 'is_own' => true])]);
    $migration = require database_path('migrations/2026_09_10_000007_sync_companies.php');
    $migration->down();
    expect(Company::find($company)->src['private_key'])->toBe('keep_private');
    $migration->up();
    $current = app(EntitySyncService::class)->current(Company::class, 'tenant_a', $company);
    expect($current['src']['is_own'])->toBeTrue();
    expect($current['src'])->not->toHaveKey('private_key');
});
