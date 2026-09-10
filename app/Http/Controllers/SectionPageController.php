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
            'main' => ['users' => 'Users', 'roles' => 'Roles', 'permissions' => 'Permissions', 'roles_rights' => 'RolesRights', 'companies' => 'Companies', 'company_contacts' => 'CompanyContacts'],
            'clients' => ['clients' => 'Clients'],
        ];
        $component = $pages[$left][$top] ?? null;
        abort_unless($component, 404);
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
                $model = config('sync.entities.'.$top.'.entity');
                $row = $model::withTrashed()->where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
                if ($top === 'company_contacts' && $request->has('company_id')) {
                    abort_unless((string) $row->company_id === (string) $request->query('company_id'), 404);
                }
            }
        }
        if ($top === 'company_contacts') {
            return app(CompanyDirectoryController::class)->contactsPage($request);
        }

        return Inertia::render($component);
    }
}
