<?php

declare(strict_types=1);

return [
    'api_path' => 'api',
    'info' => ['version' => '1.0.0', 'description' => 'WMS: авторизация и управление пользователями организации.'],
    'middleware' => ['web', App\Http\Middleware\EnsureWmsAccess::class.':admin'],
];
