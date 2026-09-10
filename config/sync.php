<?php

declare(strict_types=1);

return [
    'entities' => [
        'client_doc_types' => ['entity' => App\Models\DocType::class, 'table' => 'clients.doc_types', 'global' => true, 'authorize' => [App\Services\AccessService::class, 'isAdmin'], 'fields' => ['id', 'name', 'shortname', 'status', 'created_at', 'updated_at', 'deleted_at']],
        'client_documents' => ['entity' => App\Models\Document::class, 'table' => 'clients.documents', 'global' => false, 'authorize' => [App\Services\AccessService::class, 'isAdmin'], 'fields' => ['id', 'name', 'shortname', 'client_id', 'doc_type_id', 'status', 'comment', 'internal_comment', 'src', 'doc_date', 'accepted_at', 'payed_at', 'canceled_at', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']],
        'client_individuals' => ['entity' => App\Models\ClientIndividual::class, 'table' => 'clients.individuals', 'authorize' => [App\Services\AccessService::class, 'isAdmin'], 'fields' => ['id', 'name', 'shortname', 'firstname', 'middlename', 'lastname', 'phone', 'email', 'birthday', 'passport_seria', 'passport_number', 'passport_date', 'passport_kem', 'passport_code', 'address_reg', 'vodud_date', 'vodud_nomer', 'user_id', 'manager_id', 'client_id', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']],
        'client_companies' => ['entity' => App\Models\ClientCompany::class, 'table' => 'clients.companies', 'authorize' => [App\Services\AccessService::class, 'isAdmin'], 'fields' => ['id', 'name', 'shortname', 'fullname', 'inn', 'kpp', 'ogrn', 'okpo', 'phone', 'email', 'site', 'director_fio', 'director_position', 'bank', 'bik', 'korr_schet', 'rasch_schet', 'client_id', 'src', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']],
        'modules' => [
            'entity' => App\Models\Module::class,
            'table' => 'main.modules',
            'global' => true,
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'shortname', 'descr', 'fn', 'domain', 'status', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'features' => [
            'entity' => App\Models\Feature::class,
            'table' => 'main.features',
            'global' => true,
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'shortname', 'is_resource', 'module_id', 'status', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'icons' => [
            'entity' => App\Models\Icon::class,
            'table' => 'main.icons',
            'global' => false,
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'path', 'category', 'size', 'ext', 'user_id', 'company_id', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
        'files' => [
            'entity' => App\Models\File::class,
            'table' => 'main.files',
            'global' => false,
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'path', 'category', 'size', 'ext', 'user_id', 'company_id', 'is_s3', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],

        'clients' => [
            'entity' => App\Models\Client::class,
            'table' => 'clients.clients',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'shortname', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
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
