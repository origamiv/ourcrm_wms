<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AssistantSeeder extends BaseSeeder
{
    public $resource = 'assistant';

    public $entity = [
        'one' => 'ассистент',
        'many' => 'ассистентов',
        'about' => 'ассистенте',
    ];

    public $permissions = [
        'index' => 'Посмотреть список ассистентов',
        'create' => 'Добавить новую запись о ассистенте',
        'show' => 'Просматривать инфо о ассистенте',
        'update' => 'Редактировать инфо о ассистенте',
        'delete' => 'Удалить ассистент',
    ];

    public $roles = ['service'];
}
