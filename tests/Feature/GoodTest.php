<?php

declare(strict_types=1);

use App\Models\Good;
use App\Models\GoodCard;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000016_relate_good_cards_to_goods.php'))->up();
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->up();
    $this->admin = $this->makeUser([], true);
    $this->loginUser($this->admin);
});
afterEach(function () {
    DB::rollBack();
});

it('creates goods without cards and edits article arrays with versioned sync', function () {
    $payload = ['name' => 'Товар', 'shortname' => 'Т', 'code' => 'CODE', 'level' => 0, 'status' => 1, 'articul' => ['001', 'SKU-2'], 'barcodes' => ['4601234567890'], 'is_category' => 2, 'is_from_external' => 2];
    $row = $this->postJson('/web/goods/goods', $payload)->assertCreated()->assertJsonPath('data.goodcard_id', null)->assertJsonPath('data.articul', ['001', 'SKU-2'])->json('data');
    expect(GoodCard::count())->toBe(0);
    $this->get('/goods/goods')->assertOk();
    $this->get('/goods/goods/'.$row['id'].'/edit')->assertOk();
    $snapshot = $this->getJson('/web/sync/goods')->assertOk()->json();
    $updated = $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'articul' => ['003'], 'version' => $row['version']])->assertOk()->json('data');
    $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'version' => $row['version']])->assertConflict();
    $this->getJson('/web/sync/goods?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.data.articul', ['003']);
    $this->deleteJson('/web/goods/goods/'.$row['id'], ['version' => $updated['version']])->assertOk();
    expect(Good::withTrashed()->find($row['id'])->trashed())->toBeTrue();
});

it('restricts the main card to this good and supports multiple related cards', function () {
    $payload = ['name' => 'Товар', 'status' => 1, 'level' => 0];
    $row = $this->postJson('/web/goods/goods', $payload)->assertCreated()->json('data');
    $other = $this->postJson('/web/goods/goods', $payload)->assertCreated()->json('data');
    $card1 = DB::table('goods.good_cards')->insertGetId(['name' => 'Первая', 'good_id' => $row['id'], 'tenant_id' => 'tenant_a']);
    $card2 = DB::table('goods.good_cards')->insertGetId(['name' => 'Вторая', 'good_id' => $row['id'], 'tenant_id' => 'tenant_a']);
    $foreignCard = DB::table('goods.good_cards')->insertGetId(['name' => 'Чужая', 'good_id' => $other['id'], 'tenant_id' => 'tenant_a']);
    $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'goodcard_id' => $foreignCard, 'version' => $row['version']])->assertUnprocessable();
    $row = $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'goodcard_id' => $card2, 'version' => $row['version']])->assertOk()->assertJsonPath('data.goodcard_id', $card2)->json('data');
    expect(Good::find($row['id'])->cards()->count())->toBe(2);
    $this->getJson('/web/sync/good_cards')->assertOk()->assertJsonPath('changes.0.data.good_id', $row['id']);
    $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'goodcard_id' => null, 'version' => $row['version']])->assertOk()->assertJsonPath('data.goodcard_id', null);
});

it('rejects cycles foreign tenants and invalid article arrays and preserves source rows on sync rollback', function () {
    $payload = ['name' => 'Товар', 'status' => 1, 'level' => 0];
    $row = $this->postJson('/web/goods/goods', $payload)->assertCreated()->json('data');
    $child = $this->postJson('/web/goods/goods', [...$payload, 'parent_id' => $row['id'], 'level' => 1])->assertCreated()->json('data');
    $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'parent_id' => $child['id'], 'version' => $row['version']])->assertUnprocessable();
    $this->deleteJson('/web/goods/goods/'.$row['id'], ['version' => $row['version']])->assertUnprocessable();
    $this->postJson('/web/goods/goods', [...$payload, 'articul' => ['key' => 'x']])->assertUnprocessable();
    $this->postJson('/web/goods/goods', [...$payload, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->get('/goods/goods/'.$row['id'].'/edit')->assertNotFound();
    $this->getJson('/web/sync/goods')->assertOk()->assertJsonCount(0, 'changes');
    $this->putJson('/web/goods/goods/'.$row['id'], [...$payload, 'version' => $row['version']])->assertNotFound();
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->down();
    expect(Good::count())->toBe(2);
});

it('uses the same goods service through bearer API', function () {
    auth()->forgetGuards();
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token)->postJson('/api/goods/goods', ['name' => 'API товар', 'level' => 0, 'status' => 1, 'articul' => ['API']])->assertCreated()->assertJsonPath('data.articul.0', 'API');
});

it('migrates legacy empty card IDs and restores the owner of an existing main card', function () {
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->down();
    $migration = require database_path('migrations/2026_09_10_000016_relate_good_cards_to_goods.php');
    $migration->down();
    $card = DB::table('goods.good_cards')->insertGetId(['name' => 'Старая карточка', 'tenant_id' => 'tenant_a']);
    $empty = DB::table('goods.goods')->insertGetId(['name' => 'Без карточки', 'goodcard_id' => 0, 'level' => 0, 'tenant_id' => 'tenant_a']);
    $owner = DB::table('goods.goods')->insertGetId(['name' => 'Владелец', 'goodcard_id' => $card, 'level' => 0, 'tenant_id' => 'tenant_a']);
    $migration->up();
    expect(Good::find($empty)->goodcard_id)->toBeNull()->and((string) GoodCard::find($card)->good_id)->toBe((string) $owner);
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->up();
    $this->getJson('/web/sync/goods')->assertOk()->assertJsonCount(2, 'changes');
});

it('manages goods catalogs with tenant isolation version control and protected references', function () {
    foreach (['type_goods', 'unit_goods'] as $catalog) {
        $payload = ['name' => 'Справочник', 'shortname' => 'Спр.', 'status' => 1];
        $row = $this->postJson('/web/goods/'.$catalog, $payload)->assertCreated()->json('data');
        $this->get('/goods/'.$catalog.'/'.$row['id'].'/edit')->assertOk();
        $updated = $this->putJson('/web/goods/'.$catalog.'/'.$row['id'], [...$payload, 'name' => 'Изменён', 'version' => $row['version']])->assertOk()->json('data');
        $this->putJson('/web/goods/'.$catalog.'/'.$row['id'], [...$payload, 'version' => $row['version']])->assertConflict();
        $field = $catalog === 'type_goods' ? 'type_good' : 'type_unit';
        $good = $this->postJson('/web/goods/goods', ['name' => 'Связанный товар', 'level' => 0, 'status' => 1, $field => $row['id']])->assertCreated()->json('data');
        $this->deleteJson('/web/goods/'.$catalog.'/'.$row['id'], ['version' => $updated['version']])->assertUnprocessable();
        $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
        $this->get('/goods/'.$catalog.'/'.$row['id'].'/view')->assertNotFound();
        $this->getJson('/web/sync/'.$catalog)->assertOk()->assertJsonCount(0, 'changes');
        $this->putJson('/web/goods/'.$catalog.'/'.$row['id'], [...$payload, 'version' => $updated['version']])->assertNotFound();
        $this->loginUser($this->admin);
        $this->deleteJson('/web/goods/goods/'.$good['id'], ['version' => $good['version']])->assertOk();
        $this->deleteJson('/web/goods/'.$catalog.'/'.$row['id'], ['version' => $updated['version']])->assertOk();
    }
    auth()->forgetGuards();
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token)->postJson('/api/goods/unit_goods', ['name' => 'API единица', 'status' => 1])->assertCreated();
});
