<?php

declare(strict_types=1);

namespace Modules\messenger\Database\Seeders;

use Modules\Main\Database\Seeders\BaseSeeder;

final class AssistantDialogSeeder extends BaseSeeder
{
    public $resource = 'assistantdialog';

    public $entity = [
        'one' => 'диалоги AI ассистента',
        'many' => 'диалогов AI ассистента',
        'about' => 'диалогах AI ассистента',
    ];

    public $permissions = [
        'index' => 'Посмотреть список диалогов AI ассистента',
        'create' => 'Добавить новую запись о диалогах AI ассистента',
        'show' => 'Просматривать инфо о диалогах AI ассистента',
        'update' => 'Редактировать инфо о диалогах AI ассистента',
        'delete' => 'Удалить диалоги AI ассистента',
    ];

    public $roles = ['service'];
}
