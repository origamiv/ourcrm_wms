<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class TagSeeder extends BaseSeeder
{
    public $resource = 'tag';

    public $entity = [
        'one' => 'тэг',
        'many' => 'тэгов',
        'about' => 'тэге',
    ];

    public $permissions = [
        'index' => 'Посмотреть список тэгов',
        'create' => 'Добавить новую запись о тэге',
        'show' => 'Просматривать инфо о тэге',
        'update' => 'Редактировать инфо о тэге',
        'delete' => 'Удалить тэг',
    ];

    public $roles = ['service'];
}
