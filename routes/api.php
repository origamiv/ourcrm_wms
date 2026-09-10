<?php

declare(strict_types=1);
use App\Http\Controllers\WmsAuthController;
use App\Http\Controllers\WmsUserController;
use App\Http\Middleware\EnsureWmsAccess;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [WmsAuthController::class, 'token'])->middleware('throttle:20,1');
Route::middleware(['auth:sanctum', EnsureWmsAccess::class])->group(function () {
    Route::post('/auth/logout', [WmsAuthController::class, 'revoke']);
    Route::middleware(EnsureWmsAccess::class.':admin')->group(function () {
        Route::get('/users', [WmsUserController::class, 'index']);
        Route::get('/users/{id}', [WmsUserController::class, 'show'])->whereNumber('id');
        Route::post('/users', [WmsUserController::class, 'store']);
        Route::put('/users/{id}', [WmsUserController::class, 'update'])->whereNumber('id');
        Route::post('/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password']);
    });
});
