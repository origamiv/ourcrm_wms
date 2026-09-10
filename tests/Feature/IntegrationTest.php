<?php

declare(strict_types=1);

use App\Models\IntegrationService;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->setupPostgres();
    DB::unprepared(file_get_contents(base_path('tests/Support/integration_schema.sql')));
    (require database_path('migrations/2026_09_10_000024_sync_integrations.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('открывает шесть разделов и сохраняет записи с проверкой версии', function () {
    $this->loginUser($this->makeUser([], true));
    foreach (['webhooks' => 'IntegrationWebhooks', 'data' => 'IntegrationData', 'rules' => 'IntegrationRules', 'services' => 'IntegrationServices', 'type_hook' => 'IntegrationHookTypes', 'type_processing' => 'IntegrationProcessingTypes'] as $catalog => $page) {
        $this->get('/integration/'.$catalog)->assertOk()->assertInertia(fn (Assert $response) => $response->component($page));
        $row = $this->postJson('/web/integration/'.$catalog, ['name' => 'Тест', 'status' => 1])->assertCreated()->json('data');
        $this->get('/integration/'.$catalog.'/'.$row['id'].'/edit')->assertOk();
        $changed = $this->putJson('/web/integration/'.$catalog.'/'.$row['id'], ['name' => 'Изменено', 'status' => 2, 'version' => $row['version']])->assertOk()->json('data');
        $this->putJson('/web/integration/'.$catalog.'/'.$row['id'], ['name' => 'Конфликт', 'status' => 1, 'version' => $row['version']])->assertConflict();
        $this->getJson('/web/integration/'.$catalog.'/'.$row['id'])->assertOk()->assertJsonPath('data.name', 'Изменено')->assertHeader('Cache-Control', 'no-store, private');
        $this->deleteJson('/web/integration/'.$catalog.'/'.$row['id'], ['version' => $changed['version']])->assertOk();
        expect(DB::table('integration.'.$catalog)->where('id', $row['id'])->value('deleted_at'))->not->toBeNull();
    }
    $this->get('/integration/unknown')->assertNotFound();
});

it('проверяет ссылки и не помещает содержимое интеграций в журнал', function () {
    $this->loginUser($this->makeUser([], true));
    $service = $this->postJson('/web/integration/services', ['name' => 'Сервис', 'status' => 1])->assertCreated()->json('data');
    $rule = $this->postJson('/web/integration/rules', ['name' => 'Правило', 'status' => 1, 'params' => ['synthetic' => 'private-value']])->assertCreated()->json('data');
    $payload = ['name' => 'Вебхук', 'status' => 1, 'service_id' => $service['id'], 'rules_id' => [$rule['id']], 'url' => '/test_hook', 'params' => ['synthetic' => 'private-value']];
    $webhook = $this->postJson('/web/integration/webhooks', $payload)->assertCreated()->json('data');
    expect($webhook)->not->toHaveKeys(['params', 'url']);
    $this->getJson('/web/integration/webhooks/'.$webhook['id'])->assertOk()->assertJsonPath('details.params.synthetic', 'private-value');
    $this->deleteJson('/web/integration/services/'.$service['id'], ['version' => $service['version']])->assertUnprocessable();
    $this->deleteJson('/web/integration/rules/'.$rule['id'], ['version' => $rule['version']])->assertUnprocessable();
    $this->postJson('/web/integration/webhooks', [...$payload, 'rules_id' => [999999]])->assertUnprocessable();
    $this->postJson('/web/integration/webhooks', [...$payload, 'rules_id' => ['bad']])->assertUnprocessable();
    $this->postJson('/web/integration/data', ['name' => 'Данные', 'status' => 1, 'webhook_id' => $webhook['id'], 'raw' => 'private-value', 'data' => ['synthetic' => 'private-value']])->assertCreated();
    expect(DB::table('public.entity_changes')->where('data', 'like', '%private-value%')->exists())->toBeFalse();
    $snapshot = $this->getJson('/web/sync/integration_webhooks')->assertOk()->json();
    DB::table('integration.webhooks')->where('id', $webhook['id'])->update(['params' => json_encode(['changed' => true])]);
    $this->getJson('/web/sync/integration_webhooks?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonCount(1, 'changes');
    $this->putJson('/web/integration/webhooks/'.$webhook['id'], [...$payload, 'version' => $webhook['version']])->assertConflict();
});

it('изолирует организации и общие назначения при чтении и изменении', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $own = $this->postJson('/web/integration/services', ['name' => 'Своя', 'status' => 1])->assertCreated()->json('data');
    $this->postJson('/web/integration/services', ['name' => 'Нет', 'status' => 1, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $shared = DB::table('integration.services')->insertGetId(['name' => 'Общая', 'status' => 1]);
    $version = app(EntitySyncService::class)->current(IntegrationService::class, 'tenant_a', $shared)['version'];
    $this->putJson('/web/integration/services/'.$shared, ['name' => 'Обновлена', 'status' => 1, 'version' => $version])->assertOk()->assertJsonPath('data.tenant_id', null);
    DB::table('main.tenant_entity')->insert(['entity_type' => IntegrationService::class, 'entity_id' => (string) $shared, 'tenant_id' => 'tenant_b']);
    $this->getJson('/web/integration/services/'.$shared)->assertNotFound();
    $this->get('/integration/services/'.$shared.'/view')->assertNotFound();
    $this->postJson('/web/integration/webhooks', ['name' => 'Нет', 'status' => 1, 'service_id' => $shared])->assertUnprocessable();
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->getJson('/web/integration/services/'.$own['id'])->assertNotFound();
    $this->putJson('/web/integration/services/'.$own['id'], ['name' => 'Нет', 'status' => 1, 'version' => $own['version']])->assertNotFound();
    $this->postJson('/web/integration/webhooks', ['name' => 'Нет', 'status' => 1, 'service_id' => $own['id']])->assertUnprocessable();
    $this->loginUser($this->makeUser());
    $this->get('/integration/webhooks')->assertForbidden();
    $this->getJson('/web/sync/integration_services')->assertForbidden();
    $this->getJson('/web/integration/services/'.$shared)->assertForbidden();
    $this->postJson('/web/integration/services', ['name' => 'Нет', 'status' => 1])->assertForbidden();
});

it('поддерживает Bearer API и откат синхронизации без удаления справочников', function () {
    $admin = $this->makeUser([], true);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $row = $this->withToken($token)->postJson('/api/integration/services', ['name' => 'API', 'status' => 1])->assertCreated()->json('data');
    $this->getJson('/api/integration/services/'.$row['id'])->assertOk();
    $migration = require database_path('migrations/2026_09_10_000024_sync_integrations.php');
    $migration->down();
    expect(IntegrationService::findOrFail($row['id'])->name)->toBe('API');
    $migration->up();
    $this->getJson('/api/sync/integration_services')->assertOk()->assertJsonPath('changes.0.data.name', 'API');
});
