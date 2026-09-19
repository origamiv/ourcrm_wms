<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console'])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('scheduler:tick')->everyMinute()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
