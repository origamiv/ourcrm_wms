<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ChannelUserSeeder extends BaseSeeder
{
    public $resource = 'channeluser';

    public $entity = [
        'one' => 'пользователи канала',
        'many' => 'пользователей канала',
        'about' => 'пользователях канала',
    ];

    public $permissions = [
        'index' => 'Посмотреть список пользователей канала',
        'create' => 'Добавить новую запись о пользователях канала',
        'show' => 'Просматривать инфо о пользователях канала',
        'update' => 'Редактировать инфо о пользователях канала',
        'delete' => 'Удалить пользователи канала',
    ];

    public $roles = ['service'];
}
