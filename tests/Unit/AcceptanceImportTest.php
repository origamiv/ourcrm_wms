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
