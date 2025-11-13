<?php

declare(strict_types=1);

namespace Modules\Messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ServiceAccountSeeder extends BaseSeeder
{
    public $resource = 'serviceaccount';

    public $entity = [
        'one' => 'сервисный аккаунт',
        'many' => 'много кого чего?',
        'about' => 'о ком чем?',
    ];

    public $permissions = [
        'index' => 'Посмотреть список много кого чего?',
        'create' => 'Добавить новую запись о о ком чем?',
        'show' => 'Просматривать инфо о о ком чем?',
        'update' => 'Редактировать инфо о о ком чем?',
        'delete' => 'Удалить сервисный аккаунт',
    ];

    public $roles = ['service'];
}
