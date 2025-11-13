<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class WhiteLstSeeder extends BaseSeeder
{
    public $resource = 'whitelst';

    public $entity = [
        'one' => 'белый список',
        'many' => 'белых списков',
        'about' => 'белом списке',
    ];

    public $permissions = [
        'index' => 'Посмотреть список белых списков',
        'create' => 'Добавить новую запись о белом списке',
        'show' => 'Просматривать инфо о белом списке',
        'update' => 'Редактировать инфо о белом списке',
        'delete' => 'Удалить белый список',
    ];

    public $roles = ['service'];
}
