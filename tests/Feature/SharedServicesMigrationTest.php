<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();

    DB::unprepared(<<<'SQL'
CREATE SCHEMA access;
CREATE SCHEMA dating;
CREATE SCHEMA fakes;
CREATE SCHEMA integration;
CREATE SCHEMA keitaro;
CREATE SCHEMA messenger;
CREATE SCHEMA pay;
CREATE SCHEMA questions;
CREATE SCHEMA support;
CREATE SCHEMA video;

CREATE TABLE access.services (id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), status integer, category_id bigint, options json, cnt integer, tags json, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE clients.services (LIKE access.services INCLUDING ALL);
CREATE TABLE integration.services (LIKE access.services INCLUDING ALL);
CREATE TABLE dating.services (LIKE access.services INCLUDING ALL);
CREATE TABLE messenger.services (LIKE access.services INCLUDING ALL);
CREATE TABLE support.services (LIKE access.services INCLUDING ALL);
CREATE TABLE fakes.service (LIKE access.services INCLUDING ALL);
CREATE TABLE pay.service (LIKE access.services INCLUDING ALL);
CREATE TABLE video.service (LIKE access.services INCLUDING ALL);

CREATE TABLE access.accesses (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE clients.accounts (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE integration.data (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE integration.webhooks (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE messenger.service_accounts (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE support.credentials (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE fakes.accounts (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE fakes.bots (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE fakes.cards (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE fakes.proxies (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE keitaro.webhooks (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE questions.surveys (id bigserial PRIMARY KEY, service_id bigint);
CREATE TABLE video.hosting (id bigserial PRIMARY KEY, service_id bigint);
SQL);

    DB::table('access.services')->insert(['name' => 'Access', 'tenant_id' => 'tenant_a']);
    DB::table('clients.services')->insert(['name' => 'Client', 'tenant_id' => 'tenant_a']);
    DB::table('integration.services')->insert(['name' => 'Integration', 'tenant_id' => 'tenant_b']);
    DB::table('dating.services')->insert(['name' => 'Dating']);
    DB::table('messenger.services')->insert(['name' => 'Messenger']);
    DB::table('support.services')->insert(['name' => 'Support']);
    DB::table('fakes.service')->insert(['name' => 'Fake']);
    DB::table('pay.service')->insert(['name' => 'Pay']);
    DB::table('video.service')->insert(['name' => 'Video']);

    foreach ([
        'access.accesses' => 1,
        'clients.accounts' => 1,
        'integration.data' => 1,
        'integration.webhooks' => 1,
        'messenger.service_accounts' => 1,
        'support.credentials' => 1,
        'fakes.accounts' => 1,
        'fakes.bots' => 1,
        'fakes.cards' => 1,
        'fakes.proxies' => 1,
        'keitaro.webhooks' => 1,
        'questions.surveys' => 1,
        'video.hosting' => 1,
    ] as $table => $serviceId) {
        DB::table($table)->insert(['service_id' => $serviceId]);
    }
});

afterEach(function () {
    DB::rollBack();
});

it('переносит сервисы в shared, сохраняет резервные таблицы и переписывает ссылки', function () {
    (require database_path('migrations/2026_09_15_000700_move_services_to_shared.php'))->up();

    expect(DB::table('shared.services')->count())->toBe(9);
    expect(DB::table('shared.services')->where('source_schema', 'clients')->where('source_table', 'services')->value('name'))->toBe('Client');
    expect(DB::table('clients.services')->value('name'))->toBe('Client');
    expect(DB::table('clients.__services')->value('name'))->toBe('Client');
    expect(DB::table('pg_class')->join('pg_namespace', 'pg_namespace.oid', '=', 'pg_class.relnamespace')
        ->where('pg_namespace.nspname', 'clients')->where('pg_class.relname', 'services')->value('pg_class.relkind'))->toBe('v');

    foreach ([
        'access.accesses', 'clients.accounts', 'integration.data', 'integration.webhooks',
        'messenger.service_accounts', 'support.credentials', 'fakes.accounts', 'fakes.bots',
        'fakes.cards', 'fakes.proxies', 'keitaro.webhooks', 'questions.surveys', 'video.hosting',
    ] as $table) {
        expect(DB::table($table)->whereNotIn('service_id', DB::table('shared.services')->select('id'))->count())->toBe(0);
    }

    $createdId = DB::table('clients.services')->insertGetId(['name' => 'Created through view', 'tenant_id' => 'tenant_a']);
    expect(DB::table('shared.services')->where('id', $createdId)->where('source_schema', 'clients')->value('name'))->toBe('Created through view');

    DB::table('clients.services')->where('id', $createdId)->update(['name' => 'Updated through view']);
    expect(DB::table('shared.services')->where('id', $createdId)->value('name'))->toBe('Updated through view');

    DB::table('clients.services')->where('id', $createdId)->delete();
    expect(DB::table('shared.services')->where('id', $createdId)->value('deleted_at'))->not->toBeNull();
});
