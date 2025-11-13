<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AutomateSeeder extends BaseSeeder
{
    public $resource = 'automate';

    public $entity = [
        'one' => 'автоиатизации',
        'many' => 'сущностей',
        'about' => 'сущности',
    ];

    public $permissions = [
        'index' => 'Посмотреть список сущностей',
        'create' => 'Добавить новую запись о сущности',
        'show' => 'Просматривать инфо о сущности',
        'update' => 'Редактировать инфо о сущности',
        'delete' => 'Удалить автоиатизации',
    ];

    public $roles = ['service'];
}
