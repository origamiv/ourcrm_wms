<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AIModelSeeder extends BaseSeeder
{
    public $resource = 'aimodel';

    public $entity = [
        'one' => 'модель AI',
        'many' => 'моделей AI',
        'about' => 'модели AI',
    ];

    public $permissions = [
        'index' => 'Посмотреть список моделей AI',
        'create' => 'Добавить новую запись о модели AI',
        'show' => 'Просматривать инфо о модели AI',
        'update' => 'Редактировать инфо о модели AI',
        'delete' => 'Удалить модель AI',
    ];

    public $roles = ['service'];
}
