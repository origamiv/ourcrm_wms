<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ChannelSeeder extends BaseSeeder
{
    public $resource = 'channel';

    public $entity = [
        'one' => 'канал',
        'many' => 'каналов',
        'about' => 'канале',
    ];

    public $permissions = [
        'index' => 'Посмотреть список каналов',
        'create' => 'Добавить новую запись о канале',
        'show' => 'Просматривать инфо о канале',
        'update' => 'Редактировать инфо о канале',
        'delete' => 'Удалить канал',
    ];

    public $roles = ['service'];
}
