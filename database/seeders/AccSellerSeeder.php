<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AccSellerSeeder extends BaseSeeder
{
    public $resource = 'accseller';

    public $entity = [
        'one' => 'покупка аккаунта',
        'many' => 'покупок аккаунта',
        'about' => 'покупке аккаунта',
    ];

    public $permissions = [
        'index' => 'Посмотреть список покупок аккаунта',
        'create' => 'Добавить новую запись о покупке аккаунта',
        'show' => 'Просматривать инфо о покупке аккаунта',
        'update' => 'Редактировать инфо о покупке аккаунта',
        'delete' => 'Удалить покупка аккаунта',
    ];

    public $roles = ['service'];
}
