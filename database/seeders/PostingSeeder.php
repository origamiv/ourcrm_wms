<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class PostingSeeder extends BaseSeeder
{
    public $resource = 'posting';

    public $entity = [
        'one' => 'пост',
        'many' => 'постов',
        'about' => 'посте',
    ];

    public $permissions = [
        'index' => 'Посмотреть список постов',
        'create' => 'Добавить новую запись о посте',
        'show' => 'Просматривать инфо о посте',
        'update' => 'Редактировать инфо о посте',
        'delete' => 'Удалить пост',
    ];

    public $roles = ['service'];
}
