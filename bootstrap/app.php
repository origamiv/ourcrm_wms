<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console'])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('scheduler:tick')->everyMinute()->withoutOverlapping();
        $schedule->command('integration:sync-catalogs --marketplace=ozon')
            ->everyThirtyMinutes()
            ->onOneServer()
            ->withoutOverlapping(30);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
