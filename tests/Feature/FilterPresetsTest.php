<?php

declare(strict_types=1);

use App\Models\UserFilterPreset;

beforeEach(function (): void {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_23_000001_create_user_filter_presets.php'))->up();
});

afterEach(function (): void {
    DB::rollBack();
});

it('сохраняет персональные фильтры и активное состояние', function (): void {
    $user = $this->makeUser();
    $this->loginUser($user);
    $rules = filterPresetRules();

    $created = $this->postJson('/web/filter_presets', [
        'screen_key' => 'reference:goods',
        'name' => 'Активные товары',
        'rules' => $rules,
        'is_active' => true,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Активные товары')
        ->assertJsonPath('data.is_active', true)
        ->json('data');

    $this->getJson('/web/filter_presets?screen_key=reference:goods')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->putJson('/web/filter_presets/'.$created['id'], [
        'name' => 'Только активные',
        'rules' => $rules,
        'is_active' => false,
    ])->assertOk()
        ->assertJsonPath('data.name', 'Только активные')
        ->assertJsonPath('data.is_active', false);

    $this->deleteJson('/web/filter_presets/'.$created['id'])->assertOk();
    expect(UserFilterPreset::withTrashed()->find($created['id'])?->deleted_at)->not->toBeNull();
});

it('изолирует фильтры по пользователю и организации', function (): void {
    $owner = $this->makeUser();
    $this->loginUser($owner);
    $id = $this->postJson('/web/filter_presets', [
        'screen_key' => 'clients',
        'name' => 'Мой фильтр',
        'rules' => filterPresetRules(),
        'is_active' => true,
    ])->assertCreated()->json('data.id');

    $other = $this->makeUser(['tenant_id' => 'tenant_b']);
    $this->loginUser($other);
    $this->getJson('/web/filter_presets?screen_key=clients')->assertOk()->assertJsonCount(0, 'data');
    $this->putJson('/web/filter_presets/'.$id, [
        'name' => 'Чужой',
        'rules' => filterPresetRules(),
        'is_active' => false,
    ])->assertNotFound();
    $this->deleteJson('/web/filter_presets/'.$id)->assertNotFound();
});

it('проверяет уникальность имени и структуру дерева', function (): void {
    $this->loginUser($this->makeUser());
    $payload = [
        'screen_key' => 'users',
        'name' => 'Активные',
        'rules' => filterPresetRules(),
        'is_active' => true,
    ];
    $this->postJson('/web/filter_presets', $payload)->assertCreated();
    $this->postJson('/web/filter_presets', [...$payload, 'name' => 'активные'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
    $this->postJson('/web/filter_presets', [...$payload, 'name' => 'Пустой', 'rules' => ['glue' => 'and', 'rules' => []]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rules');

    $this->postJson('/web/filter_presets', [
        ...$payload,
        'name' => 'Слишком большой',
        'rules' => [
            'glue' => 'and',
            'rules' => [[
                'field' => 'name',
                'type' => 'text',
                'filter' => 'equal',
                'value' => str_repeat('я', 33_000),
            ]],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('rules');
});

function filterPresetRules(): array
{
    return [
        'glue' => 'and',
        'rules' => [[
            'field' => 'status',
            'type' => 'number',
            'filter' => 'equal',
            'value' => 1,
        ]],
    ];
}
