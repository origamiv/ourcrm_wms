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
    Route::get('/web/filter_presets', [App\Http\Controllers\FilterPresetController::class, 'index']);
    Route::post('/web/filter_presets', [App\Http\Controllers\FilterPresetController::class, 'store']);
    Route::put('/web/filter_presets/{id}', [App\Http\Controllers\FilterPresetController::class, 'update'])->whereNumber('id');
    Route::delete('/web/filter_presets/{id}', [App\Http\Controllers\FilterPresetController::class, 'destroy'])->whereNumber('id');
    Route::get('/web/sync/{entity_type}', [App\Http\Controllers\EntitySyncController::class, 'index'])->where('entity_type', '[a-z][a-z0-9_]*');
    Route::get('/web/directory/{entity_type}', [App\Http\Controllers\DirectoryController::class, 'index'])->where('entity_type', '[a-z][a-z0-9_]*');
    Route::get('/web/worktime/state', [App\Http\Controllers\WorktimeController::class, 'state']);
    Route::get('/web/worktime', [App\Http\Controllers\WorktimeController::class, 'calendar']);
    Route::post('/web/worktime/{action}', [App\Http\Controllers\WorktimeController::class, 'action'])->whereIn('action', ['start', 'pause', 'finish']);
    Route::get('/main/worktime', [App\Http\Controllers\WorktimeController::class, 'page']);
    Route::get('/{section}/help', fn (string $section) => Inertia::render('Instructions'))
        ->whereIn('section', ['main', 'clients', 'goods', 'integration', 'fulfillment', 'maintenance', 'logistics']);
    Route::get('/{section}/help/{id}', fn (string $section, string $id) => Inertia::render('Instructions', ['instructionId' => $id, 'sectionKey' => $section]))
        ->whereIn('section', ['main', 'clients', 'goods', 'integration', 'fulfillment', 'maintenance', 'logistics'])
        ->whereNumber('id');
    Route::get('/instructions', fn () => Inertia::render('Instructions'));
    Route::get('/instructions/{id}', fn (string $id) => Inertia::render('InstructionView', ['instructionId' => $id]))->whereNumber('id');
    Route::get('/web/instructions', [App\Http\Controllers\InstructionController::class, 'index']);
    Route::get('/web/instructions/{id}', [App\Http\Controllers\InstructionController::class, 'show'])->whereNumber('id');
    Route::get('/web/instructions/{id}/content', [App\Http\Controllers\InstructionController::class, 'content'])->whereNumber('id');
    Route::get('/web/instructions/{id}/download', [App\Http\Controllers\InstructionController::class, 'download'])->whereNumber('id');
    Route::get('/', fn () => Inertia::render('Home'))->name('home');
    Route::middleware(EnsureWmsAccess::class.':admin')->group(function () {
        Route::post('/web/export/pdf', [App\Http\Controllers\DataExportController::class, 'pdf']);
        Route::post('/web/import/{entity}', [App\Http\Controllers\DataImportController::class, 'store'])->where('entity', '[a-z][a-z0-9_]*');
        Route::get('/web/integration/{integration_catalog}/{id}', [App\Http\Controllers\IntegrationController::class, 'show'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing'])->whereNumber('id');
        Route::get('/web/integration/account_options', App\Http\Controllers\ClientIntegrationAccountOptionsController::class);
        Route::get('/web/clients/integrations/{id}/run_logs', [App\Http\Controllers\ClientIntegrationRunLogController::class, 'calendar'])->whereNumber('id');
        Route::get('/web/clients/integrations/{id}/run_logs/day', [App\Http\Controllers\ClientIntegrationRunLogController::class, 'day'])->whereNumber('id');
        Route::get('/web/clients/{clientId}/integrations/sync', App\Http\Controllers\ClientIntegrationSyncController::class)->whereNumber('clientId');
        Route::get('/web/clients/relations', App\Http\Controllers\ClientRelationsController::class);
        Route::post('/web/integration/{integration_catalog}', [App\Http\Controllers\IntegrationController::class, 'store'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing']);
        Route::put('/web/integration/{integration_catalog}/{id}', [App\Http\Controllers\IntegrationController::class, 'update'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing'])->whereNumber('id');
        Route::delete('/web/integration/{integration_catalog}/{id}', [App\Http\Controllers\IntegrationController::class, 'destroy'])->whereIn('integration_catalog', ['webhooks', 'data', 'rules', 'services', 'type_hook', 'type_processing'])->whereNumber('id');
        Route::post('/web/fulfillment/{catalog}', [App\Http\Controllers\FulfillmentCatalogController::class, 'store'])->whereIn('catalog', ['warehouses', 'marketplaces', 'delivery_services', 'type_warehouses', 'kind_warehouses', 'type_storage', 'zones', 'cells', 'cell_goods', 'acceptances', 'type_acceptance', 'type_services', 'services_ff', 'task_types', 'task_statuses', 'task_stages', 'priorities']);
        Route::post('/web/logistics/{catalog}', [App\Http\Controllers\FulfillmentCatalogController::class, 'store'])->whereIn('catalog', ['orders', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses']);
        Route::put('/web/logistics/{catalog}/{id}', [App\Http\Controllers\FulfillmentCatalogController::class, 'update'])->whereIn('catalog', ['orders', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'])->whereNumber('id');
        Route::delete('/web/logistics/{catalog}/{id}', [App\Http\Controllers\FulfillmentCatalogController::class, 'destroy'])->whereIn('catalog', ['orders', 'shipments', 'order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'])->whereNumber('id');
        Route::put('/web/fulfillment/{catalog}/{id}', [App\Http\Controllers\FulfillmentCatalogController::class, 'update'])->whereIn('catalog', ['warehouses', 'marketplaces', 'delivery_services', 'type_warehouses', 'kind_warehouses', 'type_storage', 'zones', 'cells', 'cell_goods', 'acceptances', 'type_acceptance', 'type_services', 'services_ff', 'task_types', 'task_statuses', 'task_stages', 'priorities'])->whereNumber('id');
        Route::put('/web/fulfillment/goods_marketplace/{id}', [App\Http\Controllers\MarketplaceCatalogController::class, 'update'])->whereNumber('id');
        Route::delete('/web/fulfillment/{catalog}/{id}', [App\Http\Controllers\FulfillmentCatalogController::class, 'destroy'])->whereIn('catalog', ['warehouses', 'marketplaces', 'delivery_services', 'type_warehouses', 'kind_warehouses', 'type_storage', 'zones', 'cells', 'cell_goods', 'acceptances', 'type_acceptance', 'type_services', 'services_ff', 'task_types', 'task_statuses', 'task_stages', 'priorities'])->whereNumber('id');
        Route::post('/web/fulfillment/tasks', [App\Http\Controllers\TaskController::class, 'store']);
        Route::get('/web/fulfillment/tasks/{id}/history', [App\Http\Controllers\TaskController::class, 'history'])->whereNumber('id');
        Route::get('/web/fulfillment/tasks/{id}/task_document', [App\Http\Controllers\TaskController::class, 'taskDocument'])->whereNumber('id');
        Route::get('/web/fulfillment/tasks/{id}/pick_list', [App\Http\Controllers\TaskController::class, 'pickList'])->whereNumber('id');
        Route::post('/web/fulfillment/tasks/{id}/files', [App\Http\Controllers\TaskController::class, 'uploadFile'])->whereNumber('id');
        Route::post('/web/acceptances/{id}/pick', [App\Http\Controllers\AcceptanceController::class, 'pick'])->whereNumber('id');
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
        Route::post('/web/clients/{client_catalog}', [App\Http\Controllers\ClientCatalogController::class, 'store'])->whereIn('client_catalog', ['services', 'accounts']);
        Route::put('/web/clients/{client_catalog}/{id}', [App\Http\Controllers\ClientCatalogController::class, 'update'])->whereIn('client_catalog', ['services', 'accounts'])->whereNumber('id');
        Route::delete('/web/clients/{client_catalog}/{id}', [App\Http\Controllers\ClientCatalogController::class, 'destroy'])->whereIn('client_catalog', ['services', 'accounts'])->whereNumber('id');

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
            ->whereIn('left', ['main', 'clients', 'goods', 'integration', 'fulfillment', 'maintenance', 'logistics'])->whereNumber('id')
            ->whereIn('action', ['view', 'edit', 'create', 'delete', 'password', 'logs']);

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
        Route::get('/web/imports', [App\Http\Controllers\ImportRunController::class, 'index']);
        Route::get('/web/imports/scheduler-status', [App\Http\Controllers\ImportRunController::class, 'schedulerStatus']);
        Route::get('/web/background_processes', App\Http\Controllers\BackgroundProcessController::class);
        Route::get('/web/background_processes/runs', [App\Http\Controllers\BackgroundProcessController::class, 'runs']);
        Route::get('/web/scheduler', [App\Http\Controllers\SchedulerController::class, 'index']);
        Route::get('/web/scheduler/tasks', [App\Http\Controllers\SchedulerController::class, 'tasks']);
        Route::post('/web/scheduler', [App\Http\Controllers\SchedulerController::class, 'store']);
        Route::put('/web/scheduler/{id}', [App\Http\Controllers\SchedulerController::class, 'update'])->whereNumber('id');
        Route::delete('/web/scheduler/{id}', [App\Http\Controllers\SchedulerController::class, 'destroy'])->whereNumber('id');
        Route::get('/web/scheduler/{id}/runs', [App\Http\Controllers\SchedulerController::class, 'runs'])->whereNumber('id');
        Route::get('/web/scheduler-tasks', [App\Http\Controllers\SchedulerTaskController::class, 'index']);
        Route::post('/web/scheduler-tasks', [App\Http\Controllers\SchedulerTaskController::class, 'store']);
        Route::put('/web/scheduler-tasks/{id}', [App\Http\Controllers\SchedulerTaskController::class, 'update'])->whereNumber('id');
        Route::delete('/web/scheduler-tasks/{id}', [App\Http\Controllers\SchedulerTaskController::class, 'destroy'])->whereNumber('id');
        Route::post('/web/scheduler_tasks', [App\Http\Controllers\SchedulerTaskController::class, 'store']);
        Route::put('/web/scheduler_tasks/{id}', [App\Http\Controllers\SchedulerTaskController::class, 'update'])->whereNumber('id');
        Route::delete('/web/scheduler_tasks/{id}', [App\Http\Controllers\SchedulerTaskController::class, 'destroy'])->whereNumber('id');
        Route::post('/web/instructions', [App\Http\Controllers\InstructionController::class, 'store']);
        Route::post('/web/instructions/{id}', [App\Http\Controllers\InstructionController::class, 'update'])->whereNumber('id');
        Route::delete('/web/instructions/{id}', [App\Http\Controllers\InstructionController::class, 'destroy'])->whereNumber('id');
        Route::get('/web/users/{id}', [WmsUserController::class, 'show'])->whereNumber('id');
        Route::post('/web/users', [WmsUserController::class, 'store']);
        Route::put('/web/users/{id}', [WmsUserController::class, 'update'])->whereNumber('id');
        Route::post('/web/users/{id}/{action}', [WmsUserController::class, 'action'])->whereNumber('id')->whereIn('action', ['activate', 'block', 'delete', 'restore', 'password', 'roles']);
    });
});
