<?php

declare(strict_types=1);

use App\Console\ImportTswmsCommand;

function invokeAcceptanceImportHelper(string $name, mixed $value): mixed
{
    $command = new ImportTswmsCommand;
    $method = (new ReflectionClass($command))->getMethod($name);
    $method->setAccessible(true);

    return $method->invoke($command, $value);
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
