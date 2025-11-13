<?php

namespace App\Config;

class ConfigFront
{
    public static function get()
    {
        $module = 'Front2';

        return [
            "name" => 'api',
            "title" => 'Промо',
            "api"=>"api/marketing/promocode",
            "web"=>"marketing/promocodes",
            "module" => $module,
            "fields" => [
                "name" => [
                    "type" => 'string',
                    "comment" => 'Название',
                    "nullable" => true,
                ],
                "shortname" => [
                    "type" => 'string',
                    "comment" => 'Короткое',
                    "nullable" => true,
                ],
                "date_start" => [
                    "type" => 'date',
                    "comment" => 'Дата начала',
                    "nullable" => true,
                    "default" => 0
                ],
                "date_end" => [
                    "type" => 'date',
                    "comment" => 'Дата окончания',
                    "nullable" => true,
                    "default" => 0
                ],
                "status" => [
                    "type" => 'integer',
                    "comment" => 'Статус',
                    "default" => 1,
                    "nullable" => true,
                ],
            ],

            "generate" => [
                'component' => 'stubs/front/ListPage.vue',
//                'migration' => 'stubs/maker/migration.stub.php',
//                'seeder' => 'stubs/maker/seeder.stub.php',
//                'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.stub.php',
//                'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.stub.php',
//                'request' => 'stubs/maker/request.stub.php',
//                'controller' => 'stubs/maker/controller.stub.php',
            ],
            "paths" => [
                'component' => 'Modules/' . $module . '/old/src/pages/resources/marketing/promocodes/ListPage.vue',
//                'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
//                'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
//                'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
//                'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
//                'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
//                'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
            ],
            "templates" => [
                'columns' => "<TableColumn label=\"{comment}\" field=\"{name}\"/>",                // для fillable
                'property' => '* @property {type} ${name} {comment}', // для моделей
                'field' => "\$table->{type}('{name}'){additional};",  // для миграции

            ]
        ];
    }
}
