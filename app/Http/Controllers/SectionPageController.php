<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SectionPageController
{
    public function __invoke(Request $request, string $left, string $top, ?string $id = null, ?string $action = null): Response
    {
        $pages = [
            'integration' => ['webhooks' => 'IntegrationWebhooks', 'data' => 'IntegrationData', 'rules' => 'IntegrationRules', 'services' => 'IntegrationServices', 'type_hook' => 'IntegrationHookTypes', 'type_processing' => 'IntegrationProcessingTypes'],
            'fulfillment' => ['warehouses' => 'Warehouses', 'cells' => 'Cells', 'cell_goods' => 'CellGoods', 'goods_marketplace' => 'GoodsMarketplace', 'acceptances' => 'Acceptances', 'type_acceptance' => 'TypeAcceptances', 'type_services' => 'TypeServices', 'services_ff' => 'ServicesFf', 'tasks' => 'Tasks', 'marketplaces' => 'Marketplaces', 'delivery_services' => 'DeliveryServices', 'type_warehouses' => 'TypeWarehouses', 'kind_warehouses' => 'KindWarehouses', 'type_storage' => 'TypeStorage', 'task_types' => 'TaskTypes', 'task_statuses' => 'TaskStatuses', 'task_stages' => 'TaskStages', 'priorities' => 'Priorities', 'zones' => 'Zones'],
            'goods' => ['goods' => 'Goods', 'good_cards' => 'GoodCardDetail', 'type_goods' => 'GoodTypes', 'unit_goods' => 'GoodUnits', 'kind_kiz' => 'KindKiz', 'kizes' => 'Kizes'],
            'main' => ['modules' => 'Modules', 'features' => 'Features', 'icons' => 'Icons', 'files' => 'Files', 'instructions' => 'Instructions', 'users' => 'Users', 'roles' => 'Roles', 'permissions' => 'Permissions', 'roles_rights' => 'RolesRights', 'companies' => 'Companies', 'company_contacts' => 'CompanyContacts', 'worktime' => 'Worktime'],
            'clients' => ['documents' => 'Documents', 'doc_types' => 'DocTypes', 'clients' => 'Clients', 'companies' => 'ClientCompanies', 'individuals' => 'ClientIndividuals', 'services' => 'ClientServices', 'accounts' => 'ClientAccounts'],
            'maintenance' => ['imports' => 'Imports', 'scheduler' => 'Scheduler', 'references' => 'MaintenanceReferences'],
            'logistics' => ['orders' => 'Orders', 'shipments' => 'Shipments', 'references' => 'LogisticsReferences', 'order_statuses' => 'OrderStatuses', 'order_sources' => 'OrderSources', 'order_cancel_statuses' => 'OrderCancelStatuses', 'logistic_companies' => 'LogisticCompanies', 'shipment_statuses' => 'ShipmentStatuses'],
        ];
        $entity = $left === 'clients' && $top !== 'clients' ? 'client_'.$top : $top;
        if ($left === 'integration') {
            $entity = 'integration_'.$top;
        }
        $component = $pages[$left][$top] ?? null;
        abort_unless($component, 404);
        if ($top === 'worktime') {
            return Inertia::render('Worktime');
        }
        $goodId = null;
        $taskId = null;
        $clientScope = null;
        $warehouseScope = null;
        if ($left === 'clients' && in_array($top, ['companies', 'individuals', 'documents', 'accounts'], true) && $request->has('client_id')) {
            $input = $request->validate(['client_id' => ['sometimes', 'required', 'integer', 'min:1']]);
            if (isset($input['client_id'])) {
                $client = \App\Models\Client::visibleTo($request->user()->tenant_id)->findOrFail($input['client_id']);
                $clientScope = ['id' => (string) $client->id, 'name' => $client->name ?: ($client->shortname ?: 'Клиент №'.$client->id)];
            }
        }
        if ($left === 'fulfillment' && $top === 'cells') {
            $input = $request->validate(['warehouse_id' => ['sometimes', 'required', 'integer', 'min:1']]);
            if (isset($input['warehouse_id'])) {
                $warehouse = \App\Models\Warehouse::visibleTo($request->user()->tenant_id)->findOrFail($input['warehouse_id']);
                $warehouseScope = ['id' => (string) $warehouse->id, 'name' => $warehouse->name ?: ($warehouse->shortname ?: 'Склад №'.$warehouse->id)];
            }
        }

        abort_unless(($id === null) === ($action === null), 404);
        if ($id !== null) {
            abort_if($top === 'roles_rights', 404);
            $actions = ['view', 'edit', 'create'];
            if ($top !== 'permissions') {
                $actions[] = 'delete';
            }
            if ($top === 'users') {
                $actions[] = 'password';
            }
            abort_unless(in_array($action, $actions, true), 404);
            if ($action === 'create') {
                abort_unless($id === '0', 404);
            } else {
                $model = config('sync.entities.'.$entity.'.entity');
                $query = $model::withTrashed();
                $query->visibleTo($request->user()->tenant_id);
                if ($clientScope !== null) {
                    $query->where('client_id', $clientScope['id']);
                }
                if ($warehouseScope !== null) {
                    $query->where('warehouse_id', $warehouseScope['id']);
                }
                $row = $query->findOrFail($id);
                if ($left === 'goods' && $top === 'goods' && $action === 'view') {
                    $goodId = (string) $row->id;
                    $component = 'GoodDetail';
                }
                if ($left === 'fulfillment' && $top === 'tasks' && $action === 'view') {
                    $taskId = (string) $row->id;
                    $component = 'TaskDetail';
                }
                if ($top === 'company_contacts' && $request->has('company_id')) {
                    abort_unless((string) $row->company_id === (string) $request->query('company_id'), 404);
                }
            }
        }
        if ($top === 'company_contacts') {
            return app(CompanyDirectoryController::class)->contactsPage($request);
        }

        return Inertia::render($component, ['clientScope' => $clientScope, 'warehouseScope' => $warehouseScope, 'goodId' => $goodId, 'taskId' => $taskId]);
    }
}
