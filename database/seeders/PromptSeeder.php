<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class PromptSeeder extends BaseSeeder
{
    public $resource = 'prompt';

    public $entity = [
        'one' => 'промпт',
        'many' => 'промптов',
        'about' => 'промпте',
    ];

    public $permissions = [
        'index' => 'Посмотреть список промптов',
        'create' => 'Добавить новую запись о промпте',
        'show' => 'Просматривать инфо о промпте',
        'update' => 'Редактировать инфо о промпте',
        'delete' => 'Удалить промпт',
    ];

    public $roles = ['service'];
}
