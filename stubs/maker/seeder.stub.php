<?php

namespace Modules\DummyModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Main\Database\Seeders\BaseSeeder;

class DummyClass extends BaseSeeder
{
    public $resource='DummyModelLower';
    public $entity = [
            'one'=>'{descrOne}',
            'many'=>'{descrManyR}',
            'about'=>'{descrAbout}'
        ];
    public $permissions = [
      'index'=>'Посмотреть список {descrManyR}',
      'create'=>'Добавить новую запись о {descrAbout}',
      'show'=>'Просматривать инфо о {descrAbout}',
      'update'=>'Редактировать инфо о {descrAbout}',
      'delete'=>'Удалить {descrOne}',
      ];
    public $roles=['service'];
}
