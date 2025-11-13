<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ProjectSeeder extends BaseSeeder
{
    public $resource = 'project';

    public $entity = [
        'one' => 'проект',
        'many' => 'проектов',
        'about' => 'проекте',
    ];

    public $permissions = [
        'index' => 'Посмотреть список проектов',
        'create' => 'Добавить новую запись о проекте',
        'show' => 'Просматривать инфо о проекте',
        'update' => 'Редактировать инфо о проекте',
        'delete' => 'Удалить проект',
    ];

    public $roles = ['service'];
}
