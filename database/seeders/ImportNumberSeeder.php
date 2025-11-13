<?php

declare(strict_types=1);

namespace Modules\chats\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ImportNumberSeeder extends BaseSeeder
{
    public $resource = 'importnumber';

    public $entity = [
        'one' => 'номер',
        'many' => 'номеров',
        'about' => 'номере',
    ];

    public $permissions = [
        'index' => 'Посмотреть список номеров',
        'create' => 'Добавить новую запись о номере',
        'show' => 'Просматривать инфо о номере',
        'update' => 'Редактировать инфо о номере',
        'delete' => 'Удалить номер',
    ];

    public $roles = ['service'];
}
