<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class SendMessageSeeder extends BaseSeeder
{
    public $resource = 'sendmessage';

    public $entity = [
        'one' => 'отправленное сообщение',
        'many' => 'отправленных сообщений',
        'about' => 'отправленном сообщении',
    ];

    public $permissions = [
        'index' => 'Посмотреть список отправленных сообщений',
        'create' => 'Добавить новую запись о отправленном сообщении',
        'show' => 'Просматривать инфо о отправленном сообщении',
        'update' => 'Редактировать инфо о отправленном сообщении',
        'delete' => 'Удалить отправленное сообщение',
    ];

    public $roles = ['service'];
}
