<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class MessageSeeder extends BaseSeeder
{
    public $resource = 'message';

    public $entity = [
        'one' => 'сообщение',
        'many' => 'сообщений',
        'about' => 'сообщении',
    ];

    public $permissions = [
        'index' => 'Посмотреть список сообщений',
        'create' => 'Добавить новую запись о сообщении',
        'show' => 'Просматривать инфо о сообщении',
        'update' => 'Редактировать инфо о сообщении',
        'delete' => 'Удалить сообщение',
    ];

    public $roles = ['service'];
}
