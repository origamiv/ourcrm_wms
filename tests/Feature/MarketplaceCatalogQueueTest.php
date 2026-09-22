<?php

declare(strict_types=1);

use App\Jobs\SyncMarketplaceCatalogJob;
use App\Models\ClientAccount;
use App\Models\GoodMarketplace;
use App\Models\ImportRun;
use App\Models\IntegrationData;
use App\Models\IntegrationWebhook;
use App\Services\MarketplaceCatalogDispatchService;
use App\Services\MarketplaceCatalogSyncService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->setupPostgres();
    DB::unprepared(file_get_contents(base_path('tests/Support/integration_schema.sql')));
    foreach ([
        '2026_09_10_000016_relate_good_cards_to_goods.php',
        '2026_09_15_001000_create_marketplace_catalog.php',
        '2026_09_15_001001_create_tenant_settings.php',
        '2026_09_15_001100_create_tswms_imports.php',
        '2026_09_15_001101_add_deleted_at_to_tswms_imports.php',
        '2026_09_16_000001_generalize_import_runs.php',
        '2026_09_16_000002_create_import_run_stages.php',
        '2026_09_16_000004_add_deleted_at_to_import_run_stages.php',
        '2026_09_19_000008_add_marketplace_webhook_to_import_runs.php',
        '2026_09_21_000001_add_marketplace_account_to_import_runs.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Schema::create('clients.accounts', function ($table): void {
        $table->id();
        $table->string('tenant_id');
        $table->integer('client_id')->nullable();
        $table->integer('status');
        $table->text('token');
        $table->json('src')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    $this->tenant = '11111111-1111-4111-8111-111111111111';
    $this->account = new ClientAccount;
    $this->account->forceFill(['tenant_id' => $this->tenant, 'status' => 1, 'token' => 'synthetic', 'src' => ['credentials' => ['key2' => 'test', 'business_id' => 'test']]])->save();
    $serviceId = DB::table('integration.services')->insertGetId(['shortname' => 'ozon', 'status' => 1]);
    $ruleId = DB::table('integration.rules')->insertGetId(['shortname' => 'ozon_catalog', 'val' => 'OzonCatalogRule', 'status' => 1]);
    $this->webhook = new IntegrationWebhook;
    $this->webhook->forceFill(['name' => 'Тестовый каталог', 'shortname' => 'test_catalog', 'tenant_id' => $this->tenant, 'service_id' => $serviceId, 'status' => 1, 'rules_id' => [$ruleId], 'params' => ['account_id' => $this->account->id]])->save();
    config(['cache.stores.redis' => ['driver' => 'array']]);
    Http::preventStrayRequests();
    Illuminate\Support\Sleep::fake();
    Bus::fake([SyncMarketplaceCatalogJob::class]);
});

afterEach(function (): void {
    DB::rollBack();
});

function catalogQueueJob(IntegrationWebhook $webhook, int $importId, int $attempts = 1): SyncMarketplaceCatalogJob
{
    $job = new SyncMarketplaceCatalogJob((int) $webhook->id, (string) $webhook->tenant_id, $importId);
    $transport = Mockery::mock(Illuminate\Contracts\Queue\Job::class);
    $transport->shouldReceive('getJobId')->andReturn('test-job');
    $transport->shouldReceive('attempts')->andReturn($attempts);
    $job->setJob($transport);

    return $job;
}

it('создаёт один импорт до постановки в очередь и не дублирует аккаунт', function (): void {
    $dispatcher = app(MarketplaceCatalogDispatchService::class);
    $first = $dispatcher->dispatch($this->webhook, 'ozon');
    $second = $dispatcher->dispatch($this->webhook, 'ozon');
    expect($second->id)->toBe($first->id)->and(ImportRun::count())->toBe(1);
    Bus::assertDispatchedTimes(SyncMarketplaceCatalogJob::class, 1);
    Bus::assertDispatched(SyncMarketplaceCatalogJob::class, fn ($job) => $job->importId === $first->id && $job->queue === 'imports_ozon' && $job->afterCommit);
    $other = $this->webhook->replicate();
    $other->save();
    expect($dispatcher->dispatch($other, 'ozon')->id)->toBe($first->id);
    Bus::assertDispatchedTimes(SyncMarketplaceCatalogJob::class, 1);
});

it('обрабатывает каталог отдельными заданиями с общим курсором и счётчиком', function (): void {
    Http::fakeSequence()->push(['result' => array_map(fn ($id) => ['id' => $id], range(1, 100)), 'last_id' => 'page2'])
        ->push(['result' => [['id' => 101, 'offer_id' => 'two']], 'last_id' => 'nonempty_final_cursor']);
    $run = app(MarketplaceCatalogDispatchService::class)->dispatch($this->webhook, 'ozon');
    catalogQueueJob($this->webhook, $run->id)->handle();
    expect($run->fresh()->status)->toBe('queued')
        ->and($run->fresh()->options['catalog_cursor'])->toBe('page2')
        ->and($run->fresh()->processed_records)->toBe(100);
    Http::assertSentCount(1);
    catalogQueueJob($this->webhook, $run->id)->handle();
    expect($run->fresh()->status)->toBe('completed')->and($run->fresh()->processed_records)->toBe(101)
        ->and(GoodMarketplace::count())->toBe(101)->and(ImportRun::count())->toBe(1);
    Http::assertSent(fn ($request) => ($request['last_id'] ?? null) === 'page2' && $request['limit'] === 100);
    catalogQueueJob($this->webhook, $run->id)->handle();
    Http::assertSentCount(2);
});

it('повторяет временный сбой той же страницы без нового импорта', function (): void {
    $run = app(MarketplaceCatalogDispatchService::class)->dispatch($this->webhook, 'ozon');
    $run->forceFill(['options' => ['catalog_cursor' => 'page2', 'catalog_processed' => 100], 'processed_records' => 100])->save();
    $disconnected = true;
    Http::fake(function () use (&$disconnected) {
        if ($disconnected) {
            throw new Illuminate\Http\Client\ConnectionException('Тестовый обрыв');
        }

        return Http::response(['result' => [['id' => 101]], 'last_id' => '']);
    });
    $job = catalogQueueJob($this->webhook, $run->id);
    $job->job->shouldReceive('release')->once()->with(30);
    $job->handle();
    expect($run->fresh()->status)->toBe('queued')->and($run->fresh()->options['catalog_cursor'])->toBe('page2');
    $disconnected = false;
    catalogQueueJob($this->webhook, $run->id, 2)->handle();
    expect($run->fresh()->status)->toBe('completed')->and($run->fresh()->processed_records)->toBe(101)
        ->and($run->fresh()->error_message)->toBeNull()->and(ImportRun::count())->toBe(1);
});

it('останавливает WB на последней неполной странице с непустым курсором', function (): void {
    Http::fake(['*' => Http::response(['cards' => [['nmID' => 1, 'sizes' => [['chrtID' => 11]]]], 'cursor' => ['updatedAt' => '2026-01-01', 'nmID' => 1, 'total' => 1]])]);
    $data = new IntegrationData;
    $data->forceFill(['tenant_id' => $this->tenant]);
    app(MarketplaceCatalogSyncService::class)->sync($this->webhook, $data, 'wildberries');
    Http::assertSentCount(1);
    expect($data->data['next_cursor'])->toBeNull()->and($data->data['processed'])->toBe(1);
});

it('не выдаёт произвольную ошибку 404 Ozon за успешное завершение', function (): void {
    Http::fake(['*' => Http::response([], 404)]);
    $data = new IntegrationData;
    expect(fn () => app(MarketplaceCatalogSyncService::class)->sync($this->webhook, $data, 'ozon', cursor: 'expired', singlePage: true))
        ->toThrow(Illuminate\Http\Client\RequestException::class);
});

it('не допускает преждевременную повторную выдачу зарезервированного задания', function (): void {
    expect(config('queue.connections.redis.retry_after'))->toBeGreaterThan(1800)
        ->and((new SyncMarketplaceCatalogJob(1, $this->tenant))->timeout)->toBeLessThan(1260);
});

it('завершает постоянную ошибку без повторных заданий', function (): void {
    Http::fake(['*' => Http::response([], 401)]);
    $run = app(MarketplaceCatalogDispatchService::class)->dispatch($this->webhook, 'ozon');
    $job = catalogQueueJob($this->webhook, $run->id);
    $job->job->shouldReceive('fail')->once()->with(Mockery::type(Illuminate\Http\Client\RequestException::class));
    $job->handle();
    expect($run->fresh()->status)->toBe('failed')->and($run->fresh()->stages()->first()->status)->toBe('failed');
    Bus::assertDispatchedTimes(SyncMarketplaceCatalogJob::class, 1);
    Http::assertSentCount(1);
});

it('передаёт курсор Яндекс Маркета в следующую страницу', function (): void {
    Http::fakeSequence()->push(['status' => 'OK', 'result' => ['offerMappings' => [], 'paging' => ['nextPageToken' => 'next']]])
        ->push(['status' => 'OK', 'result' => ['offerMappings' => []]]);
    $data = new IntegrationData;
    $data->forceFill(['tenant_id' => $this->tenant]);
    $service = app(MarketplaceCatalogSyncService::class);
    $service->sync($this->webhook, $data, 'yandex_market', singlePage: true);
    expect($data->data['next_cursor'])->toBe('next');
    $service->sync($this->webhook, $data, 'yandex_market', cursor: $data->data['next_cursor'], singlePage: true);
    expect($data->data['next_cursor'])->toBeNull();
    Http::assertSent(fn ($request) => str_contains($request->url(), 'page_token=next'));
});

it('сохраняет ручное сопоставление и мягкое удаление при повторе страницы', function (): void {
    $row = new GoodMarketplace;
    $row->forceFill(['tenant_id' => $this->tenant, 'webhook_id' => $this->webhook->id, 'integration_id' => $this->webhook->id,
        'marketplace' => 'ozon', 'external_id' => '42', 'good_id' => 123, 'match_type' => 'manual', 'deleted_at' => now()])->save();
    Http::fake(['*' => Http::response(['result' => [['id' => 42, 'name' => 'Обновлено']]])]);
    $data = new IntegrationData;
    $data->forceFill(['tenant_id' => $this->tenant]);
    app(MarketplaceCatalogSyncService::class)->sync($this->webhook, $data, 'ozon', singlePage: true);
    expect($row->fresh()->good_id)->toBe(123)->and($row->fresh()->match_type)->toBe('manual')
        ->and($row->fresh()->deleted_at)->not->toBeNull()->and(GoodMarketplace::withTrashed()->count())->toBe(1);
});

it('массовая команда ставит задания в очередь и пропускает уже активный аккаунт', function (): void {
    DB::table('public.tenants')->insert(['id' => $this->tenant, 'status' => 1]);
    DB::table('main.tenant_settings')->insert(['tenant_id' => $this->tenant, 'name' => 'integration.marketplace_catalog_sync', 'value' => '{"enabled":true}']);
    $typeId = DB::table('integration.type_processing')->insertGetId(['shortname' => 'marketplace_catalog', 'status' => 1]);
    DB::table('integration.rules')->update(['type_processing_id' => $typeId]);
    $this->artisan('integration:sync-catalogs', ['--marketplace' => 'ozon'])->assertSuccessful();
    $this->artisan('integration:sync-catalogs', ['--marketplace' => 'ozon'])->assertSuccessful();
    Bus::assertDispatchedTimes(SyncMarketplaceCatalogJob::class, 1);
    Http::assertNothingSent();
});
