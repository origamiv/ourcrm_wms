<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->setupPostgres();
    config(['dadata.token' => 'test_token']);
    Http::preventStrayRequests();
});
afterEach(function () {
    DB::rollBack();
});

it('maps company suggestions without exposing credentials or saving a company', function () {
    Http::fake(['suggestions.dadata.ru/*' => Http::response(['suggestions' => [['value' => 'ООО Склад', 'data' => ['inn' => '1234567890', 'kpp' => '123456789', 'ogrn' => '1234567890123', 'name' => ['short' => 'Склад', 'full_with_opf' => 'Общество Склад'], 'management' => ['name' => 'Иванов Иван', 'post' => 'Директор'], 'opf' => ['short' => 'ООО'], 'address' => ['unrestricted_value' => 'Москва, Тестовая, 1'], 'private_data' => 'not_exposed']]]])]);
    $this->loginUser($this->makeUser([], true));
    $response = $this->postJson('/web/companies/suggestions/party', ['query' => 'Склад'])->assertOk()->assertJsonPath('suggestions.0.fields.inn', '1234567890')->assertJsonPath('suggestions.0.fields.fullname', 'Общество Склад')->assertJsonPath('suggestions.0.fields.src.opf', 'ООО')->assertJsonPath('suggestions.0.fields.director_fio', 'Иванов Иван');
    expect($response->getContent())->not->toContain('test_token', 'private_data');
    expect(DB::table('main.companies')->count())->toBe(0);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Token test_token') && $request['query'] === 'Склад' && $request['count'] === 5);
});

it('maps bank details through Bearer API without guessing a company settlement account', function () {
    Http::fake(['suggestions.dadata.ru/*' => Http::response(['suggestions' => [['value' => 'Тестовый банк', 'data' => ['bic' => '044525225', 'correspondent_account' => '30101810400000000225', 'name' => ['payment' => 'ПАО Тестовый банк']]]]])]);
    $admin = $this->makeUser([], true);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token)->postJson('/api/companies/suggestions/bank', ['query' => '044525225'])->assertOk()->assertJsonPath('suggestions.0.fields.bank', 'ПАО Тестовый банк')->assertJsonPath('suggestions.0.fields.bik', '044525225')->assertJsonPath('suggestions.0.fields.korr_schet', '30101810400000000225')->assertJsonMissingPath('suggestions.0.fields.rasch_schet');
});

it('handles missing configuration and provider failures without invalidating the session', function () {
    $this->loginUser($this->makeUser([], true));
    config(['dadata.token' => null]);
    $this->postJson('/web/companies/suggestions/party', ['query' => 'Склад'])->assertStatus(503);
    Http::assertNothingSent();
    config(['dadata.token' => 'test_token']);
    Http::fake(['suggestions.dadata.ru/*' => Http::response(['secret' => 'provider_detail'], 403)]);
    $this->postJson('/web/companies/suggestions/party', ['query' => 'Склад'])->assertStatus(502)->assertJsonMissing(['secret' => 'provider_detail']);
    $this->get('/companies')->assertOk();
    Http::fake(['suggestions.dadata.ru/*' => Http::failedConnection()]);
    $this->postJson('/web/companies/suggestions/party', ['query' => 'Склад'])->assertStatus(502);
});

it('validates suggestions and restricts them to administrators', function () {
    $this->loginUser($this->makeUser([], true));
    $this->postJson('/web/companies/suggestions/party', ['query' => 'а'])->assertUnprocessable();
    $this->postJson('/web/companies/suggestions/unknown', ['query' => 'Склад'])->assertNotFound();
    $this->loginUser($this->makeUser());
    $this->postJson('/web/companies/suggestions/party', ['query' => 'Склад'])->assertForbidden();
    Http::assertNothingSent();
});
