<?php

declare(strict_types=1);

use App\Services\ImportRunService;
use Illuminate\Support\Facades\Redis;

it('находит импорт в сериализованном задании Redis без повторного чтения очередей', function (): void {
    $payload = json_encode([
        'data' => ['command' => 'O:34:"App\\Jobs\\SyncMarketplaceCatalogJob":1:{s:8:"importId";i:5812;}'],
    ], JSON_THROW_ON_ERROR);
    $connection = Mockery::mock();
    $connection->shouldReceive('lrange')->times(4)->andReturnUsing(
        fn (string $key): array => $key === 'queues:imports_wildberries' ? [$payload] : [],
    );
    $connection->shouldReceive('zrange')->times(8)->andReturn([]);
    Redis::shouldReceive('connection')->once()->andReturn($connection);

    $service = new ImportRunService;
    $method = (new ReflectionClass($service))->getMethod('hasQueuedJob');

    expect($method->invoke($service, 5812))->toBeTrue()
        ->and($method->invoke($service, 5813))->toBeFalse();
});
