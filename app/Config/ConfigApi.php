<?php

namespace App\Config;

class ConfigApi
{
    public static function get($config=null)
    {
        $module = $config['module'] ?? 'Tasks';
        $model = $config['model'] ?? 'Task';
        $table = $config['table'] ?? "tasks";
        $titleMenu=$config['titleMenu'] ?? 'Задачи';

        $modelLower=strtolower($model);
        $moduleLower=strtolower($module);
        $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
        return [
            /*
            "name" => 'api',
            "model" => $model,
            // используется в контроллере в описани Апи
            "descr" => [
                "about" => "задачах",   // Измененяет информацию о
                "many_r" => "задач", // возвращает список
                "one" => "задачу",     // Создает
            ],
            "table" => $table,
            "module" => $module,
            "fields" => [
                "name" => [
                    "type" => 'string',
                    "comment" => 'Наименование',
                    "nullable" => true,
                    "example" => "Задача"
                ],
                "shortname" => [
                    "type" => 'string',
                    "comment" => 'Краткое наименование',
                    "nullable" => true,
                    "example" => "Задача"
                ],
                "user_id" => [
                    "type" => 'integer',
                    "comment" => 'Пользователь',
                    "nullable" => true,
                    "example" => "1"
                ],
                "descr" => [
                    "type" => 'string',
                    "comment" => 'Описание задачи',
                    "nullable" => true,
                    "example" => "1"
                ],
                "status" => [
                    "type" => 'integer',
                    "comment" => 'Статус',
                    "default" => 0,
                    "nullable" => true,
                    "example" => "1"
                ],
                "plan_time" => [
                    "type" => 'string',
                    "comment" => 'Статус',
                    "nullable" => true,
                    "example" => "01.01.2025 18:30"
                ],
                "fact_time" => [
                    "type" => 'string',
                    "comment" => 'Статус',
                    "nullable" => true,
                    "example" => "01.01.2025 18:30"
                ],
                "src" => [
                    "type" => 'json',
                    "comment" => 'Api',
                    "nullable" => true,
                    "example" => "{json}"
                ],
            ],
/**/
            "generate" => [
                'model' => 'stubs/maker/model.stub.php',
                'migration' => 'stubs/maker/migration.stub.php',
                'seeder' => 'stubs/maker/seeder.stub.php',
                //'database_seeder' => 'database/Seeders/DatabaseSeeder.stub.php',
                //'menu_seeder' => 'database/seeders/Lists/MenuListsSeeder.stub.php',
                'request' => 'stubs/maker/request.stub.php',
                'controller' => 'stubs/maker/controller.stub.php',
            ],
            "paths" => [
                'model' => 'app/Models/' . $model . '.php',
                'migration' => 'database/migrations/' . $migration . '.php',
                'seeder' => 'database/seeders/' . $model . 'Seeder.php',
                'database_seeder' => 'database/seeders/DatabaseSeeder.php',
                'menu_seeder' => 'database/seeders/Lists/MenuListsSeeder.php',
                'request' => 'app/Http/Requests/' . $model . 'Request.php',
                'controller' => 'app/Http/Controllers/' . $model . 'Controller.php',
            ],
            "templates" => [
                'fillable' => "'{name}',// {comment}",                // для fillable
                'property' => '* @property {type} ${name} {comment}', // для моделей
                'field' => "\$table->{type}('{name}'){additional};",  // для миграции
                'fieldMenu' => "
                \"{name}\":{
                    \"name\": \"{comment}\",
                    \"type\": \"{type}\",
                    \"field_mode\": \"index,create,edit,show\"
                }
                ",  // для миграции
                'call' => '$this->call(' . $model . 'Seeder::class);',    // для database_seeder в модулях
                'rule' => "'{name}'=>'{additional}{type}',",          // для requests
                'attribute' => "\$attributes['{name}']='{comment}';", // для requests
                'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$module\\Models\\$model',
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
            ]
        ];
    }
}
