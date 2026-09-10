<?php

declare(strict_types=1);
use App\Models\DocType;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000009_sync_references.php'))->up();
    (require database_path('migrations/2026_09_10_000012_sync_client_documents.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('seeds six stable document types and shares them with all organizations', function () {
    expect(DB::table('clients.doc_types')->orderBy('id')->pluck('name', 'id')->all())->toBe([1 => 'Счёт', 2 => 'Акт', 3 => 'УПД', 4 => 'Договор', 5 => 'Дополнительное соглашение', 6 => 'Счёт-фактура']);
    $this->loginUser($this->makeUser([], true));
    $snapshot = $this->getJson('/web/sync/client_doc_types')->assertOk()->assertJsonCount(2, 'changes')->json();
    expect($snapshot['continuation'])->not->toBeNull();
    $this->getJson('/web/sync/client_doc_types?continuation='.urlencode($snapshot['continuation']))->assertOk()->assertJsonPath('changes.0.id', '3');
    $new = $this->postJson('/web/clients/doc_types', ['name' => 'Приложение', 'shortname' => 'Прил.', 'status' => 1])->assertCreated()->assertJsonPath('data.id', '7')->json('data');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->get('/clients/doc_types/7/edit')->assertOk();
    $updated = $this->putJson('/web/clients/doc_types/7', ['name' => 'Изменено', 'status' => 2, 'version' => $new['version']])->assertOk()->json('data');
    $this->deleteJson('/web/clients/doc_types/7', ['version' => $updated['version']])->assertOk();
    expect(DocType::withTrashed()->find(7)->trashed())->toBeTrue();
});

it('creates and edits documents with all dates comments JSON and the four statuses', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Клиент', 'tenant_id' => 'tenant_a']);
    $payload = ['client_id' => (string) $client, 'doc_type_id' => '1', 'name' => 'Счёт №1', 'shortname' => '№1', 'status' => 0, 'doc_date' => '2026-09-10', 'accepted_at' => '2026-09-11T10:15', 'payed_at' => '2026-09-12T12:30', 'canceled_at' => null, 'comment' => 'Комментарий', 'internal_comment' => 'Внутренний', 'src' => ['number' => 1, 'items' => [['name' => 'Тест']]]];
    $row = $this->postJson('/web/clients/documents', $payload)->assertCreated()->assertJsonPath('data.client_id', (string) $client)->assertJsonPath('data.doc_type_id', '1')->assertJsonPath('data.internal_comment', 'Внутренний')->assertJsonPath('data.src.items.0.name', 'Тест')->assertJsonPath('data.doc_date', '2026-09-10')->json('data');
    expect($row['accepted_at'])->toStartWith('2026-09-11T10:15');
    foreach ([1, 2, 3] as $status) {
        $row = $this->putJson('/web/clients/documents/'.$row['id'], [...$payload, 'status' => $status, 'canceled_at' => '2026-09-13T14:00', 'version' => $row['version']])->assertOk()->assertJsonPath('data.status', $status)->json('data');
    }
    $this->putJson('/web/clients/documents/'.$row['id'], [...$payload, 'version' => '0'])->assertConflict();
    $typeVersion = app(App\Services\EntitySyncService::class)->current(DocType::class, null, 1)['version'];
    $this->deleteJson('/web/clients/doc_types/1', ['version' => $typeVersion])->assertUnprocessable();
    $this->deleteJson('/web/clients/documents/'.$row['id'], ['version' => $row['version']])->assertOk();
    expect(Document::withTrashed()->find($row['id'])->trashed())->toBeTrue();
});

it('rejects invalid types status dates and cross-tenant document operations', function () {
    $this->loginUser($this->makeUser([], true));
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Свой', 'tenant_id' => 'tenant_a']);
    $foreign = DB::table('clients.clients')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    $payload = ['name' => 'Документ', 'client_id' => $client, 'doc_type_id' => 1, 'status' => 0];
    foreach ([['tenant_id' => 'tenant_b'], ['client_id' => $foreign], ['doc_type_id' => 999], ['status' => 4], ['doc_date' => '2026-02-31'], ['accepted_at' => 'bad'], ['src' => 'invalid']] as $invalid) {
        $this->postJson('/web/clients/documents', [...$payload, ...$invalid])->assertUnprocessable();
    }
    $id = DB::table('clients.documents')->insertGetId(['name' => 'Чужой документ', 'client_id' => $foreign, 'doc_type_id' => 1, 'tenant_id' => 'tenant_b']);
    $this->getJson('/web/sync/client_documents')->assertOk()->assertJsonCount(0, 'changes');
    $this->get('/clients/documents/'.$id.'/view')->assertNotFound();
    $this->putJson('/web/clients/documents/'.$id, [...$payload, 'version' => '0'])->assertNotFound();
    $this->deleteJson('/web/clients/documents/'.$id, ['version' => '0'])->assertNotFound();
    $this->loginUser($this->makeUser());
    $this->get('/clients/documents')->assertForbidden();
    $this->getJson('/web/sync/client_doc_types')->assertForbidden();
});

it('supports bearer access and direct SQL synchronization with exact bigint IDs', function () {
    $admin = $this->makeUser([], true);
    $client = '9007199254740993';
    DB::table('clients.clients')->insert(['id' => $client, 'name' => 'Большой ID', 'tenant_id' => 'tenant_a']);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $row = $this->withToken($token)->postJson('/api/clients/documents', ['name' => 'API', 'client_id' => $client, 'doc_type_id' => '1', 'status' => 3])->assertCreated()->assertJsonPath('data.client_id', $client)->json('data');
    $snapshot = $this->getJson('/api/sync/client_documents')->assertOk()->json();
    DB::table('clients.documents')->where('id', $row['id'])->update(['comment' => 'SQL']);
    $this->getJson('/api/sync/client_documents?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.data.comment', 'SQL');
    $migration = require database_path('migrations/2026_09_10_000012_sync_client_documents.php');
    $migration->down();
    expect(Document::find($row['id'])->comment)->toBe('SQL');
    $migration->up();
    $this->getJson('/api/sync/client_documents')->assertOk()->assertJsonPath('changes.0.data.client_id', $client);
});
