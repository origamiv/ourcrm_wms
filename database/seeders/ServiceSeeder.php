<?php

declare(strict_types=1);

namespace Modules\Messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class ServiceSeeder extends BaseSeeder
{
    public $resource = 'service';

    public $entity = [
        'one' => 'редактировать кого чего?',
        'many' => 'редактироватей кого чего?',
        'about' => 'редактировати кого чего?',
    ];

    public $permissions = [
        'index' => 'Посмотреть список редактироватей кого чего?',
        'create' => 'Добавить новую запись о редактировати кого чего?',
        'show' => 'Просматривать инфо о редактировати кого чего?',
        'update' => 'Редактировать инфо о редактировати кого чего?',
        'delete' => 'Удалить редактировать кого чего?',
    ];

    public $roles = ['service'];
}
