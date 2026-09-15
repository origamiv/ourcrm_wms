<?php

declare(strict_types=1);

use App\Services\MarketplaceCatalogSyncService;

it('нормализует ответ каталога Ozon из result', function (): void {
    $service = new MarketplaceCatalogSyncService;
    $method = (new ReflectionClass($service))->getMethod('normalizeOzonPage');
    $method->setAccessible(true);

    $items = $method->invoke($service, [
        'result' => [[
            'id' => 7065752,
            'sku' => 150049175,
            'offer_id' => 'tgk0156',
            'name' => 'Товар Ozon',
            'barcode' => '4607005923585',
            'barcodes' => ['4607005923585', '4607005923586'],
            'is_archived' => true,
        ]],
        'last_id' => 'next-page',
    ]);

    expect($items)->toHaveCount(1)
        ->and($items[0]['external_id'])->toBe('7065752')
        ->and($items[0]['external_sku'])->toBe('150049175')
        ->and($items[0]['offer_id'])->toBe('tgk0156')
        ->and($items[0]['barcodes'])->toBe(['4607005923585', '4607005923586'])
        ->and($items[0]['status'])->toBe(0);
});

it('поддерживает старое поле items в ответе каталога Ozon', function (): void {
    $service = new MarketplaceCatalogSyncService;
    $method = (new ReflectionClass($service))->getMethod('normalizeOzonPage');
    $method->setAccessible(true);

    $items = $method->invoke($service, [
        'items' => [[
            'id' => 42,
            'offer_id' => 'offer-42',
            'name' => 'Товар',
        ]],
    ]);

    expect($items)->toHaveCount(1)
        ->and($items[0]['external_id'])->toBe('42')
        ->and($items[0]['external_sku'])->toBe('42')
        ->and($items[0]['status'])->toBe(1);
});
