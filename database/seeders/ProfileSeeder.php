<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ProfileSeeder extends BaseSeeder
{
    public $resource = 'profile';

    public $entity = [
        'one' => 'профиль пользователя',
        'many' => 'профилей пользователя',
        'about' => 'профиле пользователя',
    ];

    public $permissions = [
        'index' => 'Посмотреть список профилей пользователя',
        'create' => 'Добавить новую запись о профиле пользователя',
        'show' => 'Просматривать инфо о профиле пользователя',
        'update' => 'Редактировать инфо о профиле пользователя',
        'delete' => 'Удалить профиль пользователя',
    ];

    public $roles = ['service'];
}
