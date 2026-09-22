<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    DB::statement('CREATE TABLE clients.accounts (id bigserial PRIMARY KEY, name varchar(255), client_id bigint, tenant_id varchar(255), deleted_at timestamp)');
    DB::unprepared(file_get_contents(base_path('tests/Support/integration_schema.sql')));
});
afterEach(function () {
    DB::rollBack();
});

it('возвращает только признаки существующих доступных связей клиента', function () {
    $this->loginUser($this->makeUser([], true));
    $first = DB::table('clients.clients')->insertGetId(['name' => 'Первый', 'tenant_id' => 'tenant_a']);
    $second = DB::table('clients.clients')->insertGetId(['name' => 'Второй', 'tenant_id' => 'tenant_a']);
    $foreign = DB::table('clients.clients')->insertGetId(['name' => 'Чужой', 'tenant_id' => 'tenant_b']);
    DB::table('clients.documents')->insert(['name' => 'Документ', 'client_id' => $first, 'doc_type_id' => 1, 'tenant_id' => 'tenant_a']);
    DB::table('clients.accounts')->insert(['name' => 'Удалённый доступ', 'client_id' => $first, 'tenant_id' => 'tenant_a', 'deleted_at' => now()]);
    DB::table('integration.webhooks')->insert(['name' => 'Интеграция', 'client_id' => $first, 'tenant_id' => 'tenant_a']);
    DB::table('clients.companies')->insert(['name' => 'Юрлицо', 'shortname' => 'Юрлицо', 'client_id' => $second, 'tenant_id' => 'tenant_a']);
    DB::table('clients.individuals')->insert(['name' => 'Физлицо', 'shortname' => 'Физлицо', 'client_id' => $second, 'tenant_id' => 'tenant_a']);
    DB::table('clients.documents')->insert(['name' => 'Чужой документ', 'client_id' => $foreign, 'doc_type_id' => 1, 'tenant_id' => 'tenant_b']);

    $this->getJson('/web/clients/relations?ids[0]='.$first.'&ids[1]='.$second.'&ids[2]='.$foreign)->assertOk()
        ->assertJsonPath('data.'.$first.'.documents', true)
        ->assertJsonPath('data.'.$first.'.accounts', false)
        ->assertJsonPath('data.'.$first.'.integrations', true)
        ->assertJsonPath('data.'.$first.'.companies', false)
        ->assertJsonPath('data.'.$first.'.individuals', false)
        ->assertJsonPath('data.'.$second.'.documents', false)
        ->assertJsonPath('data.'.$second.'.companies', true)
        ->assertJsonPath('data.'.$second.'.individuals', true)
        ->assertJsonMissingPath('data.'.$foreign);
});

it('проверяет запрос и административный доступ', function () {
    $this->loginUser($this->makeUser([], true));
    $this->getJson('/web/clients/relations')->assertUnprocessable();
    $this->getJson('/web/clients/relations?ids[0]=0')->assertUnprocessable();
    $this->loginUser($this->makeUser());
    $this->getJson('/web/clients/relations?ids[0]=1')->assertForbidden();
});
