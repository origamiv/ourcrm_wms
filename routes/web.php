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
        Route::post('/web/clients/{party}', [App\Http\Controllers\ClientPartyController::class, 'store'])->whereIn('party', ['companies', 'individuals']);
        Route::put('/web/clients/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'update'])->whereIn('party', ['companies', 'individuals'])->whereNumber('id');
        Route::delete('/web/clients/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'destroy'])->whereIn('party', ['companies', 'individuals'])->whereNumber('id');

        Route::post('/web/{reference}', [App\Http\Controllers\ReferenceController::class, 'store'])->whereIn('reference', ['modules', 'features', 'icons', 'files']);
        Route::put('/web/{reference}/{id}', [App\Http\Controllers\ReferenceController::class, 'update'])->whereIn('reference', ['modules', 'features', 'icons', 'files'])->whereNumber('id');
        Route::delete('/web/{reference}/{id}', [App\Http\Controllers\ReferenceController::class, 'destroy'])->whereIn('reference', ['modules', 'features', 'icons', 'files'])->whereNumber('id');

        foreach (['users', 'roles', 'permissions', 'roles_rights', 'companies', 'company_contacts', 'clients'] as $section) {
            Route::get('/'.$section, function (Illuminate\Http\Request $request) use ($section) {
                $left = $section === 'clients' ? 'clients' : 'main';

                return redirect('/'.$left.'/'.$section.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
            })->name($section);
        }
        Route::get('/{left}/{top}/{id?}/{action?}', App\Http\Controllers\SectionPageController::class)
            ->whereIn('left', ['main', 'clients'])->whereNumber('id')
            ->whereIn('action', ['view', 'edit', 'create', 'delete', 'password']);

        Route::post('/web/clients', [App\Http\Controllers\ClientController::class, 'store']);
        Route::put('/web/clients/{id}', [App\Http\Controllers\ClientController::class, 'update'])->whereNumber('id');
        Route::delete('/web/clients/{id}', [App\Http\Controllers\ClientController::class, 'destroy'])->whereNumber('id');
        Route::post('/web/companies/{companyId}/contacts', [App\Http\Controllers\CompanyDirectoryController::class, 'storeContact'])->whereNumber('companyId');
        Route::put('/web/companies/{companyId}/contacts/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'updateContact'])->whereNumber(['companyId', 'id']);
        Route::delete('/web/companies/{companyId}/contacts/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'destroyContact'])->whereNumber(['companyId', 'id']);
        Route::post('/web/companies/suggestions/{type}', [App\Http\Controllers\CompanySuggestionController::class, 'index'])->whereIn('type', ['party', 'bank'])->middleware('throttle:60,1');
        Route::post('/web/{directory}', [App\Http\Controllers\CompanyDirectoryController::class, 'store'])->whereIn('directory', ['companies', 'company_contacts']);
        Route::put('/web/{directory}/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'update'])->whereIn('directory', ['companies', 'company_contacts'])->whereNumber('id');
        Route::delete('/web/{directory}/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'destroy'])->whereIn('directory', ['companies', 'company_contacts'])->whereNumber('id');
        Route::put('/web/roles/{roleId}/permissions/{permissionId}', [App\Http\Controllers\RolePermissionController::class, 'update'])->whereNumber(['roleId', 'permissionId']);
        Route::delete('/web/roles/{id}', [App\Http\Controllers\AccessCatalogController::class, 'destroyRole'])->whereNumber('id');
        Route::post('/web/{catalog}', [App\Http\Controllers\AccessCatalogController::class, 'store'])->whereIn('catalog', ['roles', 'permissions']);
        Route::put('/web/{catalog}/{id}', [App\Http\Controllers\AccessCatalogController::class, 'update'])->whereIn('catalog', ['roles', 'permissions'])->whereNumber('id');
        Route::get('/web/users/sync', [WmsUserController::class, 'index']);
        Route::get('/web/users/{id}', [WmsUserController::class, 'show'])->whereNumber('id');
        Route::post('/web/users', [WmsUserController::class, 'store']);
        Route::put('/web/users/{id}', [WmsUserController::class, 'update'])->whereNumber('id');
        Route::post('/web/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password']);
    });
});
