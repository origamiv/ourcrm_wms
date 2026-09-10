<?php

declare(strict_types=1);
use App\Http\Controllers\WmsAuthController;
use App\Http\Controllers\WmsUserController;
use App\Http\Middleware\EnsureWmsAccess;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [WmsAuthController::class, 'token'])->middleware('throttle:20,1');
Route::middleware(['auth:sanctum', EnsureWmsAccess::class])->group(function () {
    Route::get('/sync/{entity_type}', [App\Http\Controllers\EntitySyncController::class, 'index'])->where('entity_type', '[a-z][a-z0-9_]*');
    Route::post('/auth/logout', [WmsAuthController::class, 'revoke']);
    Route::middleware(EnsureWmsAccess::class.':admin')->group(function () {
        Route::put('/roles/{roleId}/permissions/{permissionId}', [App\Http\Controllers\RolePermissionController::class, 'update'])->whereNumber(['roleId', 'permissionId']);
        Route::delete('/roles/{id}', [App\Http\Controllers\AccessCatalogController::class, 'destroyRole'])->whereNumber('id');
        Route::post('/{catalog}', [App\Http\Controllers\AccessCatalogController::class, 'store'])->whereIn('catalog', ['roles', 'permissions']);
        Route::put('/{catalog}/{id}', [App\Http\Controllers\AccessCatalogController::class, 'update'])->whereIn('catalog', ['roles', 'permissions'])->whereNumber('id');
        Route::get('/users', [WmsUserController::class, 'index']);
        Route::get('/users/{id}', [WmsUserController::class, 'show'])->whereNumber('id');
        Route::post('/users', [WmsUserController::class, 'store']);
        Route::put('/users/{id}', [WmsUserController::class, 'update'])->whereNumber('id');
        Route::post('/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password']);
    });
});
