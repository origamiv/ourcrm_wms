<?php

declare(strict_types=1);

use App\Console\ImportTswmsCommand;

function invokeAcceptanceImportHelper(string $name, mixed ...$arguments): mixed
{
    $command = new ImportTswmsCommand;
    $method = (new ReflectionClass($command))->getMethod($name);
    $method->setAccessible(true);

    return $method->invokeArgs($command, $arguments);
}

it('преобразует статусы приемки TSWMS в статусы WMS', function (): void {
    expect(invokeAcceptanceImportHelper('acceptanceStatus', 1))->toBe(0)
        ->and(invokeAcceptanceImportHelper('acceptanceStatus', 2))->toBe(3)
        ->and(invokeAcceptanceImportHelper('acceptanceStatus', 3))->toBe(1)
        ->and(invokeAcceptanceImportHelper('acceptanceStatus', 4))->toBe(2);
});

it('преобразует тип приемки TSWMS', function (): void {
    expect(invokeAcceptanceImportHelper('acceptanceType', 'manual'))->toBe(2)
        ->and(invokeAcceptanceImportHelper('acceptanceType', 'scan'))->toBe(1);
});

it('агрегирует активные размещения по ячейке и товару', function (): void {
    $rows = [
        (object) ['id' => 10, 'place-id' => 7, 'good-id' => 11, 'count-in-instance' => 3, 'entrance-date' => '2026-01-02 10:00:00'],
        (object) ['id' => 11, 'place-id' => 7, 'good-id' => 11, 'count-in-instance' => 5, 'entrance-date' => '2026-01-01 10:00:00'],
        (object) ['id' => 12, 'place-id' => 8, 'good-id' => 11, 'count-in-instance' => 2, 'entrance-date' => null],
    ];

    $placements = invokeAcceptanceImportHelper('aggregateCellGoods', $rows);

    expect($placements)->toHaveCount(2)
        ->and($placements[0]['source_id'])->toBe('7:11')
        ->and($placements[0]['cnt'])->toBe(8)
        ->and($placements[0]['put_at'])->toBe('2026-01-01 10:00:00')
        ->and($placements[0]['source_instance_ids'])->toBe(['10', '11'])
        ->and($placements[1]['source_id'])->toBe('8:11')
        ->and($placements[1]['cnt'])->toBe(2);
});

it('сопоставляет тип интеграции с единым сервисом', function (): void {
    expect(invokeAcceptanceImportHelper('clientServiceDefinition', 'wb'))->toBe(['wildberries', 'Wildberries'])
        ->and(invokeAcceptanceImportHelper('clientServiceDefinition', 'Wildberries FBS'))->toBe(['wildberries', 'Wildberries'])
        ->and(invokeAcceptanceImportHelper('clientServiceDefinition', 'ozon'))->toBe(['ozon', 'Ozon'])
        ->and(invokeAcceptanceImportHelper('clientServiceDefinition', 'yandex'))->toBe(['yandex_market', 'Yandex.Market'])
        ->and(invokeAcceptanceImportHelper('clientServiceDefinition', 'DNS'))->toBe(['dns', 'DNS']);
});

it('извлекает реквизиты кабинета Яндекс Маркета из кампании', function (): void {
    $credentials = invokeAcceptanceImportHelper('credentialsFromYandexCampaigns', [
        'key1' => 'api-key',
    ], [
        'campaigns' => [[
            'id' => 456,
            'business' => ['id' => 123],
        ]],
    ]);

    expect($credentials)->toBe([
        'key1' => 'api-key',
        'business_id' => '123',
        'campaign_id' => '456',
    ]);
});
