<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class CreatorSeeder extends BaseSeeder
{
    public $resource = 'creator';

    public $entity = [
        'one' => 'копирайтер',
        'many' => 'копирайтеров',
        'about' => 'копирайтере',
    ];

    public $permissions = [
        'index' => 'Посмотреть список копирайтеров',
        'create' => 'Добавить новую запись о копирайтере',
        'show' => 'Просматривать инфо о копирайтере',
        'update' => 'Редактировать инфо о копирайтере',
        'delete' => 'Удалить копирайтер',
    ];

    public $roles = ['service'];
}
