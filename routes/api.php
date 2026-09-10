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
        Route::post('/clients/{document_catalog}', [App\Http\Controllers\DocumentController::class, 'store'])->whereIn('document_catalog', ['documents', 'doc_types']);
        Route::put('/clients/{document_catalog}/{id}', [App\Http\Controllers\DocumentController::class, 'update'])->whereIn('document_catalog', ['documents', 'doc_types'])->whereNumber('id');
        Route::delete('/clients/{document_catalog}/{id}', [App\Http\Controllers\DocumentController::class, 'destroy'])->whereIn('document_catalog', ['documents', 'doc_types'])->whereNumber('id');

        Route::post('/clients/{clientId}/{party}', [App\Http\Controllers\ClientPartyController::class, 'storeScoped'])->whereIn('party', ['companies', 'individuals'])->whereNumber('clientId');
        Route::put('/clients/{clientId}/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'updateScoped'])->whereIn('party', ['companies', 'individuals'])->whereNumber('clientId')->whereNumber('id');
        Route::delete('/clients/{clientId}/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'destroyScoped'])->whereIn('party', ['companies', 'individuals'])->whereNumber('clientId')->whereNumber('id');

        Route::post('/clients/{party}', [App\Http\Controllers\ClientPartyController::class, 'store'])->whereIn('party', ['companies', 'individuals']);
        Route::put('/clients/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'update'])->whereIn('party', ['companies', 'individuals'])->whereNumber('id');
        Route::delete('/clients/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'destroy'])->whereIn('party', ['companies', 'individuals'])->whereNumber('id');

        Route::post('/{reference}', [App\Http\Controllers\ReferenceController::class, 'store'])->whereIn('reference', ['modules', 'features', 'icons', 'files']);
        Route::put('/{reference}/{id}', [App\Http\Controllers\ReferenceController::class, 'update'])->whereIn('reference', ['modules', 'features', 'icons', 'files'])->whereNumber('id');
        Route::delete('/{reference}/{id}', [App\Http\Controllers\ReferenceController::class, 'destroy'])->whereIn('reference', ['modules', 'features', 'icons', 'files'])->whereNumber('id');

        Route::post('/clients', [App\Http\Controllers\ClientController::class, 'store']);
        Route::put('/clients/{id}', [App\Http\Controllers\ClientController::class, 'update'])->whereNumber('id');
        Route::delete('/clients/{id}', [App\Http\Controllers\ClientController::class, 'destroy'])->whereNumber('id');
        Route::post('/companies/{companyId}/contacts', [App\Http\Controllers\CompanyDirectoryController::class, 'storeContact'])->whereNumber('companyId');
        Route::put('/companies/{companyId}/contacts/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'updateContact'])->whereNumber(['companyId', 'id']);
        Route::delete('/companies/{companyId}/contacts/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'destroyContact'])->whereNumber(['companyId', 'id']);
        Route::post('/companies/suggestions/{type}', [App\Http\Controllers\CompanySuggestionController::class, 'index'])->whereIn('type', ['party', 'bank'])->middleware('throttle:60,1');
        Route::post('/{directory}', [App\Http\Controllers\CompanyDirectoryController::class, 'store'])->whereIn('directory', ['companies', 'company_contacts']);
        Route::put('/{directory}/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'update'])->whereIn('directory', ['companies', 'company_contacts'])->whereNumber('id');
        Route::delete('/{directory}/{id}', [App\Http\Controllers\CompanyDirectoryController::class, 'destroy'])->whereIn('directory', ['companies', 'company_contacts'])->whereNumber('id');
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
