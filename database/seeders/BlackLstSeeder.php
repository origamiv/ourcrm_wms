<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class BlackLstSeeder extends BaseSeeder
{
    public $resource = 'blacklst';

    public $entity = [
        'one' => 'черный список',
        'many' => 'черных списков',
        'about' => 'черном списке',
    ];

    public $permissions = [
        'index' => 'Посмотреть список черных списков',
        'create' => 'Добавить новую запись о черном списке',
        'show' => 'Просматривать инфо о черном списке',
        'update' => 'Редактировать инфо о черном списке',
        'delete' => 'Удалить черный список',
    ];

    public $roles = ['service'];
}
