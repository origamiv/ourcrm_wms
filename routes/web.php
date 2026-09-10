<?php

declare(strict_types=1);
use App\Http\Controllers\WmsAuthController;
use App\Http\Controllers\WmsUserController;
use App\Http\Middleware\EnsureWmsAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', Login::class)->name('login');
Route::post('/logout', [WmsAuthController::class, 'logout'])->name('logout');
Route::middleware([EnsureWmsAccess::class, HandleInertiaRequests::class])->group(function () {
    Route::get('/', fn () => Inertia::render('Home'))->name('home');
    Route::middleware(EnsureWmsAccess::class.':admin')->group(function () {
        Route::get('/users', fn () => Inertia::render('Users'))->name('users');
        Route::get('/web/users/sync', [WmsUserController::class, 'index']);
        Route::get('/web/users/{id}', [WmsUserController::class, 'show'])->whereNumber('id');
        Route::post('/web/users', [WmsUserController::class, 'store']);
        Route::put('/web/users/{id}', [WmsUserController::class, 'update'])->whereNumber('id');
        Route::post('/web/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password']);
    });
});
