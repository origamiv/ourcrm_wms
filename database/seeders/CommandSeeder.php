<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class CommandSeeder extends BaseSeeder
{
    public $resource = 'command';

    public $entity = [
        'one' => 'команда',
        'many' => 'команд',
        'about' => 'команде',
    ];

    public $permissions = [
        'index' => 'Посмотреть список команд',
        'create' => 'Добавить новую запись о команде',
        'show' => 'Просматривать инфо о команде',
        'update' => 'Редактировать инфо о команде',
        'delete' => 'Удалить команда',
    ];

    public $roles = ['service'];
}
