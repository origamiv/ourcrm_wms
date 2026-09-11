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
        Route::post('/web/export/pdf', [App\Http\Controllers\DataExportController::class, 'pdf']);
        Route::post('/web/import/{entity}', [App\Http\Controllers\DataImportController::class, 'store'])->where('entity', '[a-z][a-z0-9_]*');
        Route::get('/web/integration/{integration_catalog}/{id}', [App\Http\Controllers\IntegrationController::class, 'show'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing'])->whereNumber('id');
        Route::post('/web/integration/{integration_catalog}', [App\Http\Controllers\IntegrationController::class, 'store'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing']);
        Route::put('/web/integration/{integration_catalog}/{id}', [App\Http\Controllers\IntegrationController::class, 'update'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing'])->whereNumber('id');
        Route::delete('/web/integration/{integration_catalog}/{id}', [App\Http\Controllers\IntegrationController::class, 'destroy'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing'])->whereNumber('id');
        Route::post('/web/fulfillment/{catalog}', [App\Http\Controllers\FulfillmentCatalogController::class, 'store'])->whereIn('catalog', ['warehouses', 'marketplaces', 'delivery_services', 'type_warehouses', 'type_storage', 'zones', 'cells', 'task_types', 'task_statuses', 'priorities']);
        Route::put('/web/fulfillment/{catalog}/{id}', [App\Http\Controllers\FulfillmentCatalogController::class, 'update'])->whereIn('catalog', ['warehouses', 'marketplaces', 'delivery_services', 'type_warehouses', 'type_storage', 'zones', 'cells', 'task_types', 'task_statuses', 'priorities'])->whereNumber('id');
        Route::delete('/web/fulfillment/{catalog}/{id}', [App\Http\Controllers\FulfillmentCatalogController::class, 'destroy'])->whereIn('catalog', ['warehouses', 'marketplaces', 'delivery_services', 'type_warehouses', 'type_storage', 'zones', 'cells', 'task_types', 'task_statuses', 'priorities'])->whereNumber('id');
        Route::post('/web/fulfillment/tasks', [App\Http\Controllers\TaskController::class, 'store']);
        Route::put('/web/fulfillment/tasks/{id}', [App\Http\Controllers\TaskController::class, 'update'])->whereNumber('id');
        Route::delete('/web/fulfillment/tasks/{id}', [App\Http\Controllers\TaskController::class, 'destroy'])->whereNumber('id');
        Route::post('/web/goods/{catalog}', [App\Http\Controllers\GoodCatalogController::class, 'store'])->whereIn('catalog', ['type_goods', 'unit_goods', 'kind_kiz']);
        Route::put('/web/goods/{catalog}/{id}', [App\Http\Controllers\GoodCatalogController::class, 'update'])->whereIn('catalog', ['type_goods', 'unit_goods', 'kind_kiz'])->whereNumber('id');
        Route::delete('/web/goods/{catalog}/{id}', [App\Http\Controllers\GoodCatalogController::class, 'destroy'])->whereIn('catalog', ['type_goods', 'unit_goods', 'kind_kiz'])->whereNumber('id');
        Route::post('/web/goods/kizes', [App\Http\Controllers\KizController::class, 'store']);
        Route::put('/web/goods/kizes/{id}', [App\Http\Controllers\KizController::class, 'update'])->whereNumber('id');
        Route::delete('/web/goods/kizes/{id}', [App\Http\Controllers\KizController::class, 'destroy'])->whereNumber('id');
        Route::post('/web/goods/goods', [App\Http\Controllers\GoodController::class, 'store']);
        Route::put('/web/goods/goods/{id}', [App\Http\Controllers\GoodController::class, 'update'])->whereNumber('id');
        Route::delete('/web/goods/goods/{id}', [App\Http\Controllers\GoodController::class, 'destroy'])->whereNumber('id');
        Route::get('/web/clients/documents/{id}/download', [App\Http\Controllers\DocumentController::class, 'download'])->whereNumber('id');
        Route::post('/web/clients/{document_catalog}', [App\Http\Controllers\DocumentController::class, 'store'])->whereIn('document_catalog', ['documents', 'doc_types']);
        Route::put('/web/clients/{document_catalog}/{id}', [App\Http\Controllers\DocumentController::class, 'update'])->whereIn('document_catalog', ['documents', 'doc_types'])->whereNumber('id');
        Route::delete('/web/clients/{document_catalog}/{id}', [App\Http\Controllers\DocumentController::class, 'destroy'])->whereIn('document_catalog', ['documents', 'doc_types'])->whereNumber('id');

        Route::post('/web/clients/{clientId}/{party}', [App\Http\Controllers\ClientPartyController::class, 'storeScoped'])->whereIn('party', ['companies', 'individuals'])->whereNumber('clientId');
        Route::put('/web/clients/{clientId}/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'updateScoped'])->whereIn('party', ['companies', 'individuals'])->whereNumber('clientId')->whereNumber('id');
        Route::delete('/web/clients/{clientId}/{party}/{id}', [App\Http\Controllers\ClientPartyController::class, 'destroyScoped'])->whereIn('party', ['companies', 'individuals'])->whereNumber('clientId')->whereNumber('id');

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
            ->whereIn('left', ['main', 'clients', 'goods', 'integration', 'fulfillment'])->whereNumber('id')
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
        Route::post('/web/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password', 'roles']);
    });
});
