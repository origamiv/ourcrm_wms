<?php

declare(strict_types=1);

use App\Models\Module;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000009_sync_references.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('shares modules and features between organizations with tenant projection versions and protected links', function () {
    $a = $this->makeUser([], true);
    $b = $this->makeUser(['tenant_id' => 'tenant_b'], true);
    $this->loginUser($a);
    $module = $this->postJson('/web/modules', ['name' => 'Модуль', 'status' => 1])->assertCreated()->json('data');
    $feature = $this->postJson('/web/features', ['name' => 'Возможность', 'module_id' => $module['id'], 'is_resource' => 1, 'status' => 1])->assertCreated()->json('data');
    $this->get('/main/modules/'.$module['id'].'/view')->assertOk();
    $this->loginUser($b);
    $snapshot = $this->getJson('/web/sync/modules')->assertOk()->assertJsonCount(1, 'changes')->json();
    $module['version'] = $snapshot['changes'][0]['version'];
    $feature['version'] = app(EntitySyncService::class)->current(App\Models\Feature::class, 'tenant_b', $feature['id'])['version'];
    $updated = $this->putJson('/web/modules/'.$module['id'], ['name' => 'Общий модуль', 'status' => 2, 'version' => $module['version']])->assertOk()->json('data');
    $this->getJson('/web/sync/modules?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.data.name', 'Общий модуль');
    $this->getJson('/web/sync/features')->assertOk()->assertJsonCount(1, 'changes');
    $this->deleteJson('/web/modules/'.$module['id'], ['version' => $updated['version']])->assertUnprocessable();
    $this->deleteJson('/web/features/'.$feature['id'], ['version' => $feature['version']])->assertOk();
    $this->deleteJson('/web/modules/'.$module['id'], ['version' => $module['version']])->assertConflict();
    $this->deleteJson('/web/modules/'.$module['id'], ['version' => $updated['version']])->assertOk();
    $this->postJson('/web/features', ['name' => 'Нет', 'module_id' => $module['id'], 'status' => 1])->assertUnprocessable();
});

it('isolates file and icon records and validates linked users and companies', function () {
    $a = $this->makeUser([], true);
    $b = $this->makeUser(['tenant_id' => 'tenant_b'], true);
    $foreignCompany = DB::table('main.companies')->insertGetId(['name' => 'Чужая', 'shortname' => 'Чужая', 'tenant_id' => 'tenant_b']);
    $this->loginUser($a);
    foreach (['icons', 'files'] as $type) {
        $payload = ['name' => 'Документ', 'path' => 'documents/test.svg', 'size' => 128, 'ext' => 'svg', 'status' => 1];
        $this->postJson('/web/'.$type, [...$payload, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
        $this->postJson('/web/'.$type, [...$payload, 'user_id' => $b->id])->assertUnprocessable();
        $this->postJson('/web/'.$type, [...$payload, 'company_id' => $foreignCompany])->assertUnprocessable();
        $row = $this->postJson('/web/'.$type, $payload)->assertCreated()->assertJsonPath('data.user_id', $a->id)->json('data');
        $this->loginUser($b);
        $this->getJson('/web/sync/'.$type)->assertOk()->assertJsonCount(0, 'changes');
        $this->get('/main/'.$type.'/'.$row['id'].'/view')->assertNotFound();
        $this->putJson('/web/'.$type.'/'.$row['id'], [...$payload, 'version' => $row['version']])->assertNotFound();
        $this->loginUser($a);
        $updated = $this->putJson('/web/'.$type.'/'.$row['id'], [...$payload, 'category' => 'Склад', 'status' => 2, 'version' => $row['version']])->assertOk()->json('data');
        $this->deleteJson('/web/'.$type.'/'.$row['id'], ['version' => $updated['version']])->assertOk();
    }
    $this->postJson('/web/files', ['name' => 'Нет', 'status' => null])->assertUnprocessable();
});

it('supports bearer mutations and captures direct global SQL changes and truncate', function () {
    $a = $this->makeUser([], true);
    $token = $this->postJson('/api/auth/token', ['email' => $a->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $module = $this->withToken($token)->postJson('/api/modules', ['name' => 'API', 'status' => 1])->assertCreated()->json('data');
    $snapshot = $this->getJson('/api/sync/modules')->assertOk()->json();
    DB::table('main.modules')->where('id', $module['id'])->update(['name' => 'SQL']);
    $delta = $this->getJson('/api/sync/modules?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.data.name', 'SQL')->json();
    DB::statement('TRUNCATE main.modules');
    $this->getJson('/api/sync/modules?cursor='.urlencode($delta['cursor']))->assertOk()->assertJsonPath('changes.0.operation', 'remove');
});

it('preserves existing reference rows across rollback and rebootstrap and forbids nonadmins', function () {
    $id = DB::table('main.modules')->insertGetId(['name' => 'Существующий']);
    $migration = require database_path('migrations/2026_09_10_000009_sync_references.php');
    $migration->down();
    expect(Module::find($id)->name)->toBe('Существующий');
    $migration->up();
    expect(app(EntitySyncService::class)->current(Module::class, null, $id)['name'])->toBe('Существующий');
    $this->loginUser($this->makeUser());
    foreach (['modules', 'features', 'icons', 'files'] as $type) {
        $this->get('/main/'.$type)->assertForbidden();
        $this->getJson('/web/sync/'.$type)->assertForbidden();
        $this->postJson('/web/'.$type, ['name' => 'Нет', 'status' => 1])->assertForbidden();
    }
});
