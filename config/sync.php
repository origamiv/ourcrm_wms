<?php

declare(strict_types=1);

return [
    'entities' => [
        'companies' => [
            'entity' => App\Models\Company::class,
            'table' => 'main.companies',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'shortname', 'fullname', 'inn', 'kpp', 'ogrn', 'phone', 'email', 'site', 'director_fio', 'director_position', 'bank', 'bik', 'korr_schet', 'rasch_schet', 'src', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'company_contacts' => [
            'entity' => App\Models\CompanyContact::class,
            'table' => 'main.company_contacts',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'shortname', 'company_id', 'val', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'users' => [
            'entity' => App\Models\User::class,
            'table' => 'public.users',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'last_name', 'middle_name', 'nick', 'email', 'phone', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'roles' => [
            'entity' => App\Models\Role::class,
            'table' => 'main.roles',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'slug', 'description', 'system', 'tags', 'status', 'company_id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'permissions' => [
            'entity' => App\Models\Permission::class,
            'table' => 'main.permissions',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'slug', 'resource', 'system', 'status', 'module_id', 'feature_id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'permission_roles' => [
            'entity' => App\Models\PermissionRole::class,
            'table' => 'main.permission_role',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'role_id', 'permission_id', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
    ],
];
