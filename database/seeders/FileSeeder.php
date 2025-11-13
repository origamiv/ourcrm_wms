<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class FileSeeder extends BaseSeeder
{
    public $resource = 'file';

    public $entity = [
        'one' => 'файл',
        'many' => 'файлов',
        'about' => 'файле',
    ];

    public $permissions = [
        'index' => 'Посмотреть список файлов',
        'create' => 'Добавить новую запись о файле',
        'show' => 'Просматривать инфо о файле',
        'update' => 'Редактировать инфо о файле',
        'delete' => 'Удалить файл',
    ];

    public $roles = ['service'];
}
