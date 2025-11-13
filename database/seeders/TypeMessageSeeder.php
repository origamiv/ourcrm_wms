<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class TypeMessageSeeder extends BaseSeeder
{
    public $resource = 'typemessage';

    public $entity = [
        'one' => 'тип сообщения',
        'many' => 'типов сообщения',
        'about' => 'типе сообщения',
    ];

    public $permissions = [
        'index' => 'Посмотреть список типов сообщения',
        'create' => 'Добавить новую запись о типе сообщения',
        'show' => 'Просматривать инфо о типе сообщения',
        'update' => 'Редактировать инфо о типе сообщения',
        'delete' => 'Удалить тип сообщения',
    ];

    public $roles = ['service'];
}
