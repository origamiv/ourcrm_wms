<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class MessengerSeeder extends BaseSeeder
{
    public $resource = 'messenger';

    public $entity = [
        'one' => 'мессенжер',
        'many' => 'мессенжеров',
        'about' => 'мессенжере',
    ];

    public $permissions = [
        'index' => 'Посмотреть список мессенжеров',
        'create' => 'Добавить новую запись о мессенжере',
        'show' => 'Просматривать инфо о мессенжере',
        'update' => 'Редактировать инфо о мессенжере',
        'delete' => 'Удалить мессенжер',
    ];

    public $roles = ['service'];
}
