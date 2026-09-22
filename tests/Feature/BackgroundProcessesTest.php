<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->setupPostgres();
    DB::unprepared(file_get_contents(base_path('tests/Support/integration_schema.sql')));
    DB::statement('CREATE TABLE wms.import_runs (
        id bigserial PRIMARY KEY,
        tenant_id varchar(255) NOT NULL,
        source_webhook_id bigint,
        status varchar(20) NOT NULL,
        processed_records integer DEFAULT 0,
        created_at timestamp,
        updated_at timestamp,
        deleted_at timestamp
    )');
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-22 12:34:20', 'Europe/Moscow'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    DB::rollBack();
});

it('показывает страницу только администратору', function (): void {
    $this->loginUser($this->makeUser());
    $this->get('/maintenance/background_processes')->assertForbidden();

    $this->loginUser($this->makeUser(['email' => 'admin@example.test'], true));
    $this->get('/maintenance/background_processes')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('BackgroundProcesses'));
});

it('возвращает одну итоговую строку по всем доступным клиентам', function (): void {
    $this->loginUser($this->makeUser([], true));
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Альфа', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $otherClient = DB::table('clients.clients')->insertGetId(['name' => 'Бета', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $inactiveClient = DB::table('clients.clients')->insertGetId(['name' => 'Неактивный', 'status' => 2, 'tenant_id' => 'tenant_a']);
    $first = webhookForStatistics('Первый', 1, 'tenant_a', $client);
    webhookForStatistics('Без запусков', 3, 'tenant_a', $client);
    webhookForStatistics('Второй активный', 1, 'tenant_a', $otherClient);
    $disabled = webhookForStatistics('Отключённый', 2, 'tenant_a', $otherClient);
    webhookForStatistics('Без клиента', 1, 'tenant_a', null);
    $foreign = webhookForStatistics('Чужой', 1, 'tenant_b', $client);
    $inactiveClientWebhook = webhookForStatistics('Неактивный клиент', 1, 'tenant_a', $inactiveClient);
    $deleted = webhookForStatistics('Удалённый', 1, 'tenant_a', $client, now());

    foreach (['completed', 'failed', 'queued', 'running'] as $index => $status) {
        runForStatistics($first, 'tenant_a', $status, now()->startOfDay()->addHours(8 + $index));
    }
    runForStatistics($first, 'tenant_a', 'completed', now()->subDay());
    runForStatistics($first, 'tenant_b', 'completed', now()->startOfDay()->addHours(10));
    runForStatistics($disabled, 'tenant_a', 'completed', now()->startOfDay()->addHours(10));
    runForStatistics($foreign, 'tenant_b', 'completed', now()->startOfDay()->addHours(10));
    runForStatistics($inactiveClientWebhook, 'tenant_a', 'completed', now()->startOfDay()->addHours(10));
    runForStatistics($deleted, 'tenant_a', 'completed', now()->startOfDay()->addHours(10));

    $this->getJson('/web/background_processes?group_by=clients&period=today')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('group_by', 'clients')
        ->assertJsonPath('bucket_unit', 'hour')
        ->assertJsonPath('total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'all')
        ->assertJsonPath('data.0.name', 'Все клиенты')
        ->assertJsonPath('data.0.total_runs', 4)
        ->assertJsonPath('data.0.successful_runs', 0)
        ->assertJsonPath('data.0.has_successful_runs', 0)
        ->assertJsonPath('data.0.failed_runs', 0)
        ->assertJsonPath('data.0.running_runs', 1)
        ->assertJsonPath('data.0.without_runs', 1)
        ->assertJsonCount(4, 'data.0.activity');
});

it('возвращает одну итоговую строку по всем доступным вебхукам', function (): void {
    $this->loginUser($this->makeUser([], true));
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Клиент', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $withRun = webhookForStatistics('Альфа', 1, 'tenant_a', $client);
    webhookForStatistics('Бета', 3, 'tenant_a', null);
    runForStatistics($withRun, 'tenant_a', 'running', now()->subMinutes(5));

    $this->getJson('/web/background_processes?group_by=webhooks&period=minutes_15')
        ->assertOk()
        ->assertJsonPath('bucket_unit', 'minute')
        ->assertJsonPath('total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'all')
        ->assertJsonPath('data.0.name', 'Все вебхуки')
        ->assertJsonPath('data.0.total_runs', 1)
        ->assertJsonPath('data.0.running_runs', 1)
        ->assertJsonPath('data.0.without_runs', 0)
        ->assertJsonCount(1, 'data.0.activity');
});

it('классифицирует уникальные сущности по последнему запуску', function (): void {
    $this->loginUser($this->makeUser([], true));
    $clients = collect(['А', 'Б', 'В', 'Г'])->map(fn (string $name): int => DB::table('clients.clients')->insertGetId([
        'name' => $name,
        'status' => 1,
        'tenant_id' => 'tenant_a',
    ]));
    $firstWebhook = webhookForStatistics('А-1', 1, 'tenant_a', $clients[0]);
    $latestFirstWebhook = webhookForStatistics('А-2', 1, 'tenant_a', $clients[0]);
    $successfulWebhook = webhookForStatistics('Б', 1, 'tenant_a', $clients[1]);
    $runningWebhook = webhookForStatistics('В', 1, 'tenant_a', $clients[2]);
    webhookForStatistics('Г', 1, 'tenant_a', $clients[3]);

    runForStatistics($firstWebhook, 'tenant_a', 'completed', now()->startOfDay()->addHours(8));
    runForStatistics($latestFirstWebhook, 'tenant_a', 'failed', now()->startOfDay()->addHours(9));
    runForStatistics($successfulWebhook, 'tenant_a', 'completed', now()->startOfDay()->addHours(10));
    runForStatistics($runningWebhook, 'tenant_a', 'queued', now()->startOfDay()->addHours(11));

    $this->getJson('/web/background_processes?group_by=clients&period=today')
        ->assertOk()
        ->assertJsonPath('data.0.successful_runs', 1)
        ->assertJsonPath('data.0.has_successful_runs', 1)
        ->assertJsonPath('data.0.failed_runs', 0)
        ->assertJsonPath('data.0.running_runs', 1)
        ->assertJsonPath('data.0.without_runs', 1);

    $this->getJson('/web/background_processes?group_by=webhooks&period=today')
        ->assertOk()
        ->assertJsonPath('data.0.successful_runs', 2)
        ->assertJsonPath('data.0.has_successful_runs', 0)
        ->assertJsonPath('data.0.failed_runs', 1)
        ->assertJsonPath('data.0.running_runs', 1)
        ->assertJsonPath('data.0.without_runs', 1);
});

it('возвращает запуски выбранного квадрата календаря', function (): void {
    $this->loginUser($this->makeUser([], true));
    $client = DB::table('clients.clients')->insertGetId(['name' => 'Клиент', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $webhook = webhookForStatistics('Вебхук', 1, 'tenant_a', $client);
    runForStatistics($webhook, 'tenant_a', 'completed', now()->startOfDay()->addHours(10)->addMinutes(15), 12);
    runForStatistics($webhook, 'tenant_a', 'failed', now()->startOfDay()->addHours(11)->addMinutes(15), 3);

    $query = http_build_query([
        'group_by' => 'clients',
        'period' => 'today',
        'bucket_start' => now()->startOfDay()->addHours(10)->toIso8601String(),
    ]);

    $this->getJson('/web/background_processes/runs?'.$query)
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.entity_id', (string) $client)
        ->assertJsonPath('data.0.status', 'completed')
        ->assertJsonPath('data.0.processed_records', 12);

    $query = http_build_query([
        'group_by' => 'webhooks',
        'period' => 'today',
        'bucket_start' => now()->startOfDay()->addHours(10)->toIso8601String(),
    ]);

    $this->getJson('/web/background_processes/runs?'.$query)
        ->assertOk()
        ->assertJsonPath('data.0.entity_id', (string) $webhook);
});

it('возвращает границы и единицы всех поддерживаемых периодов', function (string $period, string $unit, string $selectedFrom, string $selectedTo): void {
    $this->loginUser($this->makeUser([], true));

    $this->getJson('/web/background_processes?period='.$period)
        ->assertOk()
        ->assertJsonPath('period', $period)
        ->assertJsonPath('bucket_unit', $unit)
        ->assertJsonPath('timezone', 'Europe/Moscow')
        ->assertJsonPath('selected_from', $selectedFrom)
        ->assertJsonPath('selected_to', $selectedTo)
        ->assertJsonPath('statistics_from', $selectedFrom);
})->with([
    ['today', 'hour', '2026-09-22T00:00:00+03:00', '2026-09-23T00:00:00+03:00'],
    ['yesterday', 'hour', '2026-09-21T00:00:00+03:00', '2026-09-22T00:00:00+03:00'],
    ['week', 'day', '2026-09-21T00:00:00+03:00', '2026-09-28T00:00:00+03:00'],
    ['month', 'day', '2026-09-01T00:00:00+03:00', '2026-10-01T00:00:00+03:00'],
    ['hours_4', 'minute', '2026-09-22T08:35:00+03:00', '2026-09-22T12:35:00+03:00'],
    ['hour', 'minute', '2026-09-22T11:35:00+03:00', '2026-09-22T12:35:00+03:00'],
    ['minutes_15', 'minute', '2026-09-22T12:20:00+03:00', '2026-09-22T12:35:00+03:00'],
]);

it('агрегирует любое число вебхуков в одну строку', function (): void {
    $this->loginUser($this->makeUser([], true));
    foreach (range(1, 51) as $number) {
        webhookForStatistics(sprintf('Вебхук %02d', $number), 1, 'tenant_a', null);
    }

    $this->getJson('/web/background_processes?group_by=webhooks')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.without_runs', 51);
});

it('отклоняет неизвестные параметры', function (): void {
    $this->loginUser($this->makeUser([], true));
    $this->getJson('/web/background_processes?group_by=projects&period=year')->assertUnprocessable();
});

function webhookForStatistics(string $name, int $status, string $tenant, ?int $client, mixed $deletedAt = null): int
{
    return DB::table('integration.webhooks')->insertGetId([
        'name' => $name,
        'status' => $status,
        'tenant_id' => $tenant,
        'client_id' => $client,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => $deletedAt,
    ]);
}

function runForStatistics(int $webhook, string $tenant, string $status, mixed $createdAt, int $processedRecords = 0): void
{
    DB::table('wms.import_runs')->insert([
        'tenant_id' => $tenant,
        'source_webhook_id' => $webhook,
        'status' => $status,
        'processed_records' => $processedRecords,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}
