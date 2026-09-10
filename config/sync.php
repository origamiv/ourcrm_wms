<?php

declare(strict_types=1);

return [
    'entities' => [
        'users' => [
            'entity' => App\Models\User::class,
            'table' => 'public.users',
            'authorize' => [App\Services\AccessService::class, 'isAdmin'],
            'fields' => ['id', 'name', 'last_name', 'middle_name', 'nick', 'email', 'phone', 'status', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'],
        ],
    ],
];
