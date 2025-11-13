<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class MessUserSeeder extends BaseSeeder
{
    public $resource = 'messuser';

    public $entity = [
        'one' => 'пользователь мессенжера',
        'many' => 'пользователей мессенжера',
        'about' => 'пользователе мессенжера',
    ];

    public $permissions = [
        'index' => 'Посмотреть список пользователей мессенжера',
        'create' => 'Добавить новую запись о пользователе мессенжера',
        'show' => 'Просматривать инфо о пользователе мессенжера',
        'update' => 'Редактировать инфо о пользователе мессенжера',
        'delete' => 'Удалить пользователь мессенжера',
    ];

    public $roles = ['service'];
}
