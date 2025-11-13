<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AccountSeeder extends BaseSeeder
{
    public $resource = 'account';

    public $entity = [
        'one' => 'аккаунт',
        'many' => 'аккаунтов',
        'about' => 'аккаунте',
    ];

    public $permissions = [
        'index' => 'Посмотреть список аккаунтов',
        'create' => 'Добавить новую запись о аккаунте',
        'show' => 'Просматривать инфо о аккаунте',
        'update' => 'Редактировать инфо о аккаунте',
        'delete' => 'Удалить аккаунт',
    ];

    public $roles = ['service'];
}
