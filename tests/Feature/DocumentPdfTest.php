<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\DocumentPdfService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000009_sync_references.php'))->up();
    (require database_path('migrations/2026_09_10_000012_sync_client_documents.php'))->up();
    foreach (config('document_print') as $id => $print) {
        DB::table('clients.doc_types')->where('id', $id)->update(['settings' => json_encode(['print' => $print])]);
    }
    $this->admin = $this->makeUser([], true);
    $this->loginUser($this->admin);
    $this->client = DB::table('clients.clients')->insertGetId(['name' => 'Клиент', 'tenant_id' => 'tenant_a']);
    $this->executor = DB::table('main.companies')->insertGetId(['name' => 'Исполнитель ООО', 'shortname' => 'Исполнитель', 'inn' => '1234567890', 'src' => '{"is_own":true,"legal_address":"Москва"}', 'tenant_id' => 'tenant_a']);
    $this->customer = DB::table('clients.companies')->insertGetId(['name' => 'Заказчик ООО', 'shortname' => 'Заказчик', 'client_id' => $this->client, 'tenant_id' => 'tenant_a']);
    $this->payload = ['name' => 'Документ <script>secret</script>', 'client_id' => $this->client, 'doc_type_id' => 1, 'status' => 0, 'executor_id' => $this->executor, 'customer_id' => $this->customer, 'internal_comment' => 'INTERNAL_SECRET', 'src' => ['private' => 'SRC_SECRET', 'pdf' => ['number' => '42', 'items' => [['name' => 'Хранение', 'unit' => 'сутки', 'quantity' => '1.125', 'price' => '10.01', 'vat_rate' => '20']], 'subject' => 'Хранение груза', 'terms' => 'Согласованные условия', 'contract_number' => '11', 'contract_date' => '2026-09-10', 'changes' => 'Изменение условий']]];
});
afterEach(function () {
    DB::rollBack();
});

it('saves linked parties and generates all six PDFs with schema fields and exact totals', function () {
    foreach (range(1, 6) as $type) {
        $saved = $this->postJson('/web/clients/documents', [...$this->payload, 'doc_type_id' => $type])->assertCreated()->assertJsonPath('data.executor_id', (string) $this->executor)->assertJsonPath('data.customer_id', (string) $this->customer)->json('data');
        $document = Document::findOrFail($saved['id']);
        $data = app(DocumentPdfService::class)->data($document);
        expect($data['net'])->toBe(1126)->and($data['vat'])->toBe(225)->and($data['total'])->toBe(1351);
        $html = view('pdf.document', $data)->render();
        expect($html)->toContain('Исполнитель ООО', 'Заказчик ООО', '&lt;script&gt;')->not->toContain('INTERNAL_SECRET', 'SRC_SECRET', '<script>');
        $response = $this->getJson('/web/clients/documents/'.$saved['id'].'/download')->assertOk()->assertHeader('content-type', 'application/pdf');
        expect($response->getContent())->toStartWith('%PDF-');
        file_put_contents('/tmp/wms-document-'.$type.'.pdf', $response->getContent());
        expect($document->fresh()->status)->toBe(0);
    }
});

it('rejects foreign parties and missing content and supports the bearer download', function () {
    DB::table('clients.companies')->where('id', $this->customer)->update(['tenant_id' => 'tenant_b']);
    $this->postJson('/web/clients/documents', $this->payload)->assertUnprocessable();
    DB::table('clients.companies')->where('id', $this->customer)->update(['tenant_id' => 'tenant_a']);
    $saved = $this->postJson('/web/clients/documents', $this->payload)->assertCreated()->json('data');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->getJson('/web/clients/documents/'.$saved['id'].'/download')->assertNotFound();
    $this->loginUser($this->admin);
    DB::table('main.companies')->where('id', $this->executor)->update(['src' => '{"is_own":false}']);
    $this->getJson('/web/clients/documents/'.$saved['id'].'/download')->assertUnprocessable();
    DB::table('main.companies')->where('id', $this->executor)->update(['src' => '{"is_own":true}']);
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    auth()->forgetGuards();
    $this->withToken($token)->getJson('/api/clients/documents/'.$saved['id'].'/download')->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->loginUser($this->admin);
    DB::table('clients.documents')->where('id', $saved['id'])->update(['src' => '{"pdf":{"items":[]}}']);
    $this->getJson('/web/clients/documents/'.$saved['id'].'/download')->assertUnprocessable();
});

it('uses type settings for custom print fields and synchronizes settings edits', function () {
    $version = app(App\Services\EntitySyncService::class)->current(App\Models\DocType::class, 'tenant_a', 1)['version'];
    $print = config('document_print.1');
    $print['fields'][] = ['key' => 'warehouse', 'label' => 'Склад', 'type' => 'text', 'required' => true];
    $this->putJson('/web/clients/doc_types/1', ['name' => 'Счёт', 'status' => 1, 'version' => $version, 'settings' => ['print' => $print]])->assertOk()->assertJsonPath('data.settings.print.fields.5.key', 'warehouse');
    $saved = $this->postJson('/web/clients/documents', $this->payload)->assertCreated()->json('data');
    $this->getJson('/web/clients/documents/'.$saved['id'].'/download')->assertUnprocessable()->assertJsonValidationErrors('pdf.warehouse');
    $payload = $this->payload;
    $payload['src']['pdf']['warehouse'] = 'Основной склад';
    $this->putJson('/web/clients/documents/'.$saved['id'], [...$payload, 'version' => $saved['version']])->assertOk();
    $data = app(DocumentPdfService::class)->data(Document::find($saved['id']));
    expect(view('pdf.document', $data)->render())->toContain('Основной склад');
});
