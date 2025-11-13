<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AssistantChatSeeder extends BaseSeeder
{
    public $resource = 'assistantchat';

    public $entity = [
        'one' => 'чат AI ассистента',
        'many' => 'чатов AI ассистента',
        'about' => 'чате AI ассистента',
    ];

    public $permissions = [
        'index' => 'Посмотреть список чатов AI ассистента',
        'create' => 'Добавить новую запись о чате AI ассистента',
        'show' => 'Просматривать инфо о чате AI ассистента',
        'update' => 'Редактировать инфо о чате AI ассистента',
        'delete' => 'Удалить чат AI ассистента',
    ];

    public $roles = ['service'];
}
