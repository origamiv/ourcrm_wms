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
    Route::get('/web/sync/{entity_type}', [App\Http\Controllers\EntitySyncController::class, 'index'])->where('entity_type', '[a-z][a-z0-9_]*');
    Route::get('/', fn () => Inertia::render('Home'))->name('home');
    Route::middleware(EnsureWmsAccess::class.':admin')->group(function () {
        Route::get('/companies', fn () => Inertia::render('Companies'))->name('companies');
        Route::get('/company_contacts', fn () => Inertia::render('CompanyContacts'))->name('company_contacts');
        Route::post('/web/{directory}', [App\Http\Controllers\CompanyDirectoryController::class, 'store'])->whereIn('directory', ['companies', 'company_contacts']);
        Route::put('/web/{directory}/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'update'])->whereIn('directory', ['companies', 'company_contacts'])->whereNumber('id');
        Route::delete('/web/{directory}/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'destroy'])->whereIn('directory', ['companies', 'company_contacts'])->whereNumber('id');
        Route::put('/web/roles/{roleId}/permissions/{permissionId}', [App\Http\Controllers\RolePermissionController::class, 'update'])->whereNumber(['roleId', 'permissionId']);
        Route::delete('/web/roles/{id}', [App\Http\Controllers\AccessCatalogController::class, 'destroyRole'])->whereNumber('id');
        Route::post('/web/{catalog}', [App\Http\Controllers\AccessCatalogController::class, 'store'])->whereIn('catalog', ['roles', 'permissions']);
        Route::put('/web/{catalog}/{id}', [App\Http\Controllers\AccessCatalogController::class, 'update'])->whereIn('catalog', ['roles', 'permissions'])->whereNumber('id');
        Route::get('/roles_rights', fn () => Inertia::render('RolesRights'))->name('roles_rights');
        Route::get('/roles', fn () => Inertia::render('Roles'))->name('roles');
        Route::get('/permissions', fn () => Inertia::render('Permissions'))->name('permissions');
        Route::get('/users', fn () => Inertia::render('Users'))->name('users');
        Route::get('/web/users/sync', [WmsUserController::class, 'index']);
        Route::get('/web/users/{id}', [WmsUserController::class, 'show'])->whereNumber('id');
        Route::post('/web/users', [WmsUserController::class, 'store']);
        Route::put('/web/users/{id}', [WmsUserController::class, 'update'])->whereNumber('id');
        Route::post('/web/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password']);
    });
});
