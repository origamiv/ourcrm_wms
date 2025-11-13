<?php

namespace App\Config;

class ConfigModule
{
    public $string;
    public $config = [];
    public $module;
    public $generateArray = [];

    public function get()
    {
        $this->string = '/money/valute';
        $this->config = [];
        $this->module = 'Money';

        $this->generateArray = [];
        $string = '/staff/candidates';
        $config = [];
        $module = 'Staff';
        $generateArray = [
            'model' => 'stubs/maker/model.stub.php',
            'migration' => 'stubs/maker/migration.stub.php',
            'seeder' => 'stubs/maker/seeder.stub.php',
            'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.stub.php',
            'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.stub.php',
            'request' => 'stubs/maker/request.stub.php',
            'controller' => 'stubs/maker/controller.stub.php',
        ];

        $moduleLower = strtolower($this->module);
        $this->$moduleLower();

        return $this->config;
    }

    public function profit()
    {
        if ($this->string == '/profit/sites') {
            $model = 'Site';
            $table = "sites";
            $titleMenu = 'Сайты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сайтах",   // Измененяет информацию о
                    "many_r" => "сайтов", // возвращает список
                    "one" => "сайт",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Домен',
                        "nullable" => true,
                        'example' => 'www.mail.ru',
                    ],
                    "ip" => [
                        "type" => 'string',
                        "comment" => 'IP',
                        "nullable" => true,
                        "default" => "34.118.16.4",
                        "example" => "34.118.16.4"
                    ],
                    "archive" => [
                        "type" => 'string',
                        "comment" => 'Данные архива',
                        "nullable" => true,
                        "example" => "124555"
                    ],
                    "archive_id" => [
                        "type" => 'integer',
                        "comment" => 'Архив',
                        "nullable" => true,
                        "example" => "124555"
                    ],
                    "dns_old" => [
                        "type" => 'json',
                        "comment" => 'DNS старый',
                        "nullable" => true,
                        "example" => "124555"
                    ],
                    "dns" => [
                        "type" => 'json',
                        "comment" => 'DNS',
                        "nullable" => true,
                        "example" => "124555"
                    ],
                    "is_reload" => [
                        "type" => 'integer',
                        "comment" => 'Архив',
                        "nullable" => true,
                        "example" => "124555"
                    ],

                    "system_id" => [
                        "type" => 'integer',
                        "comment" => 'Система',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "geo" => [
                        "type" => 'string',
                        "comment" => 'ГЕО',
                        "nullable" => true,
                        "example" => "UA,KZ"
                    ],
                    "type_traf" => [
                        "type" => 'string',
                        "comment" => 'Тип трафика',
                        "nullable" => true,
                        "example" => "MOB"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/profit/generator') {
            $model = 'Generator';
            $table = "generator";
            $titleMenu = 'Генераторы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "генераторах",   // Измененяет информацию о
                    "many_r" => "генераторов", // возвращает список
                    "one" => "генератор",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Финансы',
                    ],
                    "size" => [
                        "type" => 'integer',
                        "comment" => 'Размер фразы в словах',
                        "nullable" => true,
                        "default" => "3",
                        "example" => "3"
                    ],
                    "words" => [
                        "type" => 'text',
                        "comment" => 'Список слов',
                        "nullable" => true,
                        "example" => "finance table like bank any good thanks"
                    ],
                    "zones" => [
                        "type" => 'text',
                        "comment" => 'Список доменных зон',
                        "nullable" => true,
                        "example" => "shop store"
                    ],
                    "system_id" => [
                        "type" => 'integer',
                        "comment" => 'Система',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "geo" => [
                        "type" => 'string',
                        "comment" => 'ГЕО',
                        "nullable" => true,
                        "example" => "UA,KZ"
                    ],
                    "type_traf" => [
                        "type" => 'string',
                        "comment" => 'Тип трафика',
                        "nullable" => true,
                        "example" => "MOB"
                    ],
                    "white_tema" => [
                        "type" => 'string',
                        "comment" => 'Тематика вайта',
                        "nullable" => true,
                        "example" => "finance_ai"
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "cnt_buy" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во покупок',
                        "nullable" => true,
                        "default" => 0,
                        "example" => "15"
                    ],
                    "cnt_arc" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во свободных архивов',
                        "nullable" => true,
                        "default" => 0,
                        "example" => "15"
                    ],
                    "cnt_white" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во вайтов для покупки',
                        "nullable" => true,
                        "default" => 0,
                        "example" => "15"
                    ],
                    "cnt_repeat" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во повторов',
                        "nullable" => true,
                        "default" => 0,
                        "example" => "15"
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Испробованные варианты',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/profit/archive') {
            $model = 'Archive';
            $table = "archive";
            $titleMenu = 'Архивы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "архиве",   // Измененяет информацию о
                    "many_r" => "архивов", // возвращает список
                    "one" => "архив",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'site.zip',
                    ],
                    "size" => [
                        "type" => 'integer',
                        "comment" => 'Размер',
                        "nullable" => true,
                        "example" => "3557"
                    ],
                    "url" => [
                        "type" => 'string',
                        "comment" => 'URL',
                        "nullable" => true,
                    ],
                    "system_id" => [
                        "type" => 'integer',
                        "comment" => 'Система',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/profit/generator_archive') {
            $model = 'GeneratorArchive';
            $table = "generator_archives";
            $titleMenu = 'Архивы в генераторах';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "архиве",   // Измененяет информацию о
                    "many_r" => "архивов", // возвращает список
                    "one" => "архив",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "generator_id" => [
                        "type" => 'integer',
                        "example" => "3"
                    ],
                    "archive_id" => [
                        "type" => 'integer',
                        "example" => "3"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/profit/services') {
            $model = 'Service';
            $table = "service";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервисе",   // Измененяет информацию о
                    "many_r" => "сервисов", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги - типы сервисов',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/profit/accounts') {
            $model = 'Account';
            $table = "account";
            $titleMenu = 'Аккаунты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "акаунте",   // Измененяет информацию о
                    "many_r" => "аккаунтов", // возвращает список
                    "one" => "аккаунт",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "host" => [
                        "type" => 'string',
                        "comment" => 'Хост',
                        "nullable" => true,
                        "example" => "www.mail.ru"
                    ],
                    "login" => [
                        "type" => 'string',
                        "comment" => 'Логин',
                        "nullable" => true,
                        "example" => "joker"
                    ],
                    "password" => [
                        "type" => 'string',
                        "comment" => 'Пароль',
                        "nullable" => true,
                        "example" => "c523de3"
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Все данные',
                        "nullable" => true,
                    ],
                    "token" => [
                        "type" => 'string',
                        "comment" => 'Токен',
                        "nullable" => true,
                        "example" => "x34dfgg534gghghggu7445"
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "balance" => [
                        "type" => 'float',
                        "comment" => 'Баланс',
                        "default" => 0,
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
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------

    }

    public function integration()
    {
        if ($this->string == '/integration/service') {
            $model = 'Service';
            $table = "services";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "cервисах",   // Измененяет информацию о
                    "many_r" => "сервисах", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Яндекс.Формы',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'ya.forms',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/integration/type_hook') {
            $model = 'TypeHook';
            $table = "type_hook";
            $titleMenu = 'Типы вебхуков';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типах обработки",   // Измененяет информацию о
                    "many_r" => "типов обработки", // возвращает список
                    "one" => "тип обработки",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Скрипт',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'script',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/integration/type_processing') {
            $model = 'TypeProcessing';
            $table = "type_processing";
            $titleMenu = 'Типы обработки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типах обработки",   // Измененяет информацию о
                    "many_r" => "типов обработки", // возвращает список
                    "one" => "тип обработки",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Скрипт',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'script',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/integration/rule') {
            $model = 'Rule';
            $table = "rules";
            $titleMenu = 'Правила обработки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "правилах обработки",   // Измененяет информацию о
                    "many_r" => "правилах обработки", // возвращает список
                    "one" => "правило обработки",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'номер телефона',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'phone',
                    ],
                    "type_processing_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип обработки',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "val" => [
                        "type" => 'string',
                        "comment" => 'Обработчик',
                        "nullable" => true,
                        'example' => 'PhoneRule',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/integration/webhook') {
            $model = 'Webhook';
            $table = "webhooks";
            $titleMenu = 'Вебхуки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "вебхуках",   // Измененяет информацию о
                    "many_r" => "вебхуков", // возвращает список
                    "one" => "вебхук",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Исполнители из Яндекс Форм',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'ya_form_executors',
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "type_hook_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип хука',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "url" => [
                        "type" => 'string',
                        "comment" => 'URL',
                        "nullable" => true,
                        'example' => '/integration/6xs4fr',
                    ],
                    "rules_id" => [
                        "type" => 'jsonb',
                        "comment" => 'Правила обработки',
                        "nullable" => true,
                        'example' => '[1, 5, 8]',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Количество обращений',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "dat_last_run" => [
                        "type" => 'timestamp',
                        "comment" => 'Время последнего обращения',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '2018-02-08 12:45:34',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/integration/data') {
            $model = 'Data';
            $table = "data";
            $titleMenu = 'Данные';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "данных",   // Измененяет информацию о
                    "many_r" => "данных", // возвращает список
                    "one" => "данное",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Вася',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'vasya',
                    ],
                    "webhook_id" => [
                        "type" => 'integer',
                        "comment" => 'Вебхук',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "src" => [
                        "type" => 'text',
                        "comment" => 'Необработанные данные',
                        "nullable" => true,
                        'example' => 'Vasya;Pupkin;+7890123456;завтра',
                    ],
                    "data" => [
                        "type" => 'jsonb',
                        "comment" => 'Обработанные данные',
                        "nullable" => true,
                        'example' => '{\"phone\":\"+7890123456\"}',
                    ],
                    "progress_processing" => [
                        "type" => 'jsonb',
                        "comment" => 'Примененные правила обработки',
                        "nullable" => true,
                        'example' => '[1, 4]',
                    ],
                    "is_processing" => [
                        "type" => 'integer',
                        "comment" => 'Обработано',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------

        if ($this->string == '/integration/lsnet_products') {
            $model = 'LsnetProduct';
            $table = "lsnet_products";
            $titleMenu = 'продукты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "правилах обработки",   // Измененяет информацию о
                    "many_r" => "правилах обработки", // возвращает список
                    "one" => "правило обработки",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'номер телефона',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'phone',
                    ],
                    "shop" => [
                        "type" => 'string',
                        "comment" => 'Магазин',
                        "nullable" => true,
                        'example' => 'phone',
                    ],
                    "brand" => [
                        "type" => 'string',
                        "comment" => 'Бренд',
                        "nullable" => true,
                        'example' => 'phone',
                    ],
                    "href" => [
                        "type" => 'string',
                        "comment" => 'Страница',
                        "nullable" => true,
                        'example' => 'phone',
                    ],
                    "articul" => [
                        "type" => 'string',
                        "comment" => 'артикул',
                        "nullable" => true,
                        'example' => 'phone',
                    ],
                    "price" => [
                        "type" => 'integer',
                        "comment" => 'Цена',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function messenger()
    {
        if ($this->string == '/messenger/type_chats') {
            $model = 'TypeMessage';
            $table = "type_messages";
            $titleMenu = 'Типы сообщений';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе сообщений",   // Измененяет информацию о
                    "many_r" => "типов сообщений", // возвращает список
                    "one" => "тип сообщения",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'message',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "other" => [
                        "type" => 'json',
                        "comment" => 'Название в других сервисах ',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string = '/messenger/files') {
            $model = 'File';
            $table = "files";
            $titleMenu = 'Файлы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе сообщений",   // Измененяет информацию о
                    "many_r" => "типов сообщений", // возвращает список
                    "one" => "тип сообщения",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'message',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "account_id" => [
                        "type" => 'integer',
                        "comment" => 'Аккаунт',
                        "nullable" => true,
                    ],
                    "channel_id" => [
                        "type" => 'integer',
                        "comment" => 'Канал',
                        "nullable" => true,
                    ],
                    "message_id" => [
                        "type" => 'integer',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во скачиваний',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "path" => [
                        "type" => 'string',
                        "comment" => 'Путь  ',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function loya()
    {
        if ($this->string == '/loya/border') {
            $model = 'Border';
            $table = "borders";
            $titleMenu = 'Границы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "границе",// Измененяет информацию о
                    "many_r" => "границ",// возвращает список
                    "one" => "границу", // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'название границы',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'граница',
                    ],
                    "leftVal" => [
                        "type" => 'integer',
                        "comment" => 'Левая граница',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "rightVal" => [
                        "type" => 'integer',
                        "comment" => 'Правая граница',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/borderlevel') {
            $model = 'BorderLevel';
            $table = "border_levels";
            $titleMenu = 'Границы уровней';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "границе уровня",   // Измененяет информацию о
                    "many_r" => "границ уровней", // возвращает список
                    "one" => "границу уровня",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'название границы уровня',
                    ],
                    "border_id" => [
                        "type" => 'integer',
                        "comment" => 'Граница',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "level_id" => [
                        "type" => 'integer',
                        "comment" => 'уровень',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/level') {
            $model = 'Level';
            $table = "levels";
            $titleMenu = 'Уровни лояльности';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "уровне лояльности",// Измененяет информацию о
                    "many_r" => "уровней лояльности", // возвращает список
                    "one" => "уровень лояльности", // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'название уровня лояльности',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'лояльность',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "type_level_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип уровня лояльности',
                        "default" => 1,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание для приложения',
                        "default" => 'Уровень позволяет получить хороший кешбек',
                        "nullable" => true,
                        'example' => 'описание',
                    ],
                    "background" => [
                        "type" => 'string',
                        "comment" => 'Цвет фона карты лояльности',
                        "default" => '#DDDDDD',
                        "nullable" => true,
                        'example' => '#ffffff',
                    ],
                    "is_base" => [
                        "type" => 'integer',
                        "comment" => 'Является базовым',
                        "default" => 2,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "is_accumulation" => [
                        "type" => 'integer',
                        "comment" => 'Является накопительным',
                        "default" => 1,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/phonepoint') {
            $model = 'PhonePoint';
            $table = "phone_points";
            $titleMenu = 'Список телефонов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "телефоне",// Измененяет информацию о
                    "many_r" => "телефонов",// возвращает список
                    "one" => "телефон",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                        'example' => '+17012345678',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "cnt_points" => [
                        "type" => 'integer',
                        "comment" => 'Количество баллов',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/phonelevel') {
            $model = 'PhoneLevel';
            $table = "phone_level";
            $titleMenu = 'Список телефонов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "телефоне",// Измененяет информацию о
                    "many_r" => "телефонов",// возвращает список
                    "one" => "телефон",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                        'example' => '+17012345678',
                    ],
                    "level_id" => [
                        "type" => 'integer',
                        "comment" => 'Уровень лояльности',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/program') {
            $model = 'Program';
            $table = "programs";
            $titleMenu = 'Программы лояльности';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "программе лояльности",// Измененяет информацию о
                    "many_r" => "программ лояльности",// возвращает список
                    "one" => "программу лояльности",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Программа лояльности',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'Программа',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/rule') {
            $model = 'Rule';
            $table = "rules";
            $titleMenu = 'Правила';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "правиле",// Измененяет информацию о
                    "many_r" => "правил",// возвращает список
                    "one" => "правило",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Правило',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'prv',
                    ],
                    "check" => [
                        "type" => 'string',
                        "comment" => 'Условие',
                        "nullable" => true,
                        'example' => 'условие1 > условие2',
                    ],
                    "formula" => [
                        "type" => 'string',
                        "comment" => 'Формула',
                        "nullable" => true,
                        'example' => '*',
                    ],
                    "program_id" => [
                        "type" => 'integer',
                        "comment" => 'Программа лояльности',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "num" => [
                        "type" => 'integer',
                        "comment" => 'Порядковый номер',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "priority" => [
                        "type" => 'integer',
                        "comment" => 'Приоритет',
                        "default" => 5,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/tag') {
            $model = 'Tag';
            $table = "tag";
            $titleMenu = 'Теги';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "тэге",// Измененяет информацию о
                    "many_r" => "тэгов",// возвращает список
                    "one" => "тег",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'tag',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'tg',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Количество',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/transaction') {
            $model = 'Transaction';
            $table = "transactions";
            $titleMenu = 'Транзакции';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "транзакции",// Измененяет информацию о
                    "many_r" => "транзакций",// возвращает список
                    "one" => "транзакцию",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'транзакция 1',
                    ],
                    "date_transact" => [
                        "type" => 'dateTime',
                        "comment" => 'Дата транзакции',
                        "nullable" => true,
                        'example' => '2023-01-01 12:00:00',
                    ],
                    "date_start_activity" => [
                        "type" => 'dateTime',
                        "comment" => 'Дата и время начала активности баллов',
                        "nullable" => true,
                        'example' => '2023-01-01 15:00:00',
                    ],
                    "date_end_activity" => [
                        "type" => 'dateTime',
                        "comment" => 'Дата и время окончания активности баллов',
                        "nullable" => true,
                        'example' => '2023-02-01 15:00:00',
                    ],
                    "is_active" => [
                        "type" => 'boolean',
                        "comment" => 'Активность баллов',
                        "default" => 'false',
                        "nullable" => true,
                        'example' => 'true',
                    ],
                    "direction" => [
                        "type" => 'string',
                        "comment" => 'Направление',
                        "default" => 'in',
                        "nullable" => true,
                        'example' => 'in',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Количество балов',
                        "nullable" => true,
                        'example' => '4',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "program_id" => [
                        "type" => 'integer',
                        "comment" => 'Программа лояльности',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "wallet_id" => [
                        "type" => 'integer',
                        "comment" => 'Кошелек',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "staff_id" => [
                        "type" => 'integer',
                        "comment" => 'Сотрудник',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "cheque_id" => [
                        "type" => 'integer',
                        "comment" => 'Чек',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "tag_id" => [
                        "type" => 'integer',
                        "comment" => 'Тег',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "tags" => [
                        "type" => 'jsonb',
                        "comment" => 'Теги',
                        "nullable" => true,
                        'example' => '[1,2,3]',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/typedowngrade') {
            $model = 'TypeDowngrade';
            $table = "type_downgrade";
            $titleMenu = 'Типы даундгрейда уровней';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе даундгрейда уровней",// Измененяет информацию о
                    "many_r" => "типов даундгрейда уровней",// возвращает список
                    "one" => "тип даундгрейда уровня",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'downgrade 1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'dwg 1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/typelevel') {
            $model = 'TypeLevel';
            $table = "type_level";
            $titleMenu = 'Типы уровней';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе уровня",// Измененяет информацию о
                    "many_r" => "типов уровней",// возвращает список
                    "one" => "тип уровня",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'level 1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'lev 1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/variable') {
            $model = 'Variable';
            $table = "variables";
            $titleMenu = 'Переменные';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "переменной",// Измененяет информацию о
                    "many_r" => "переменных",// возвращает список
                    "one" => "переменную",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'variable 1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'var',
                    ],
                    "type" => [
                        "type" => 'string',
                        "comment" => 'Тип переменной',
                        "nullable" => true,
                        'example' => 'integer',
                    ],
                    "values" => [
                        "type" => 'string',
                        "comment" => 'Значение',
                        "nullable" => true,
                        'example' => '4',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/loya/wallet') {
            $model = 'Wallet';
            $table = "wallets";
            $titleMenu = 'Кошельки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "кошельке",// Измененяет информацию о
                    "many_r" => "кошельков",// возвращает список
                    "one" => "кошелек",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'wallet 1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '4',
                    ],
                    "program_id" => [
                        "type" => 'integer',
                        "comment" => 'Программа лояльности',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "balance" => [
                        "type" => 'float',
                        "comment" => 'Баланс',
                        "default" => 0,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "defer" => [
                        "type" => 'float',
                        "comment" => 'Отложенные баллы',
                        "default" => 0,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function ref()
    {
        if ($this->string == '/ref/program') {
            $model = 'Program';
            $table = "program";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "cервисах",   // Измененяет информацию о
                    "many_r" => "сервисах", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Яндекс.Формы',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'ya.forms',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------


    }

    public function goods()
    {
        if ($this->string == '/goods/good') {
            $model = 'Good';
            $table = "goods";
            $titleMenu = 'Товары';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "товаре",   // Измененяет информацию о
                    "many_r" => "товаров", // возвращает список
                    "one" => "товар",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "parent_id" => [
                        "type" => 'integer',
                        "comment" => 'Родительский id',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "parent_code" => [
                        "type" => 'string',
                        "comment" => 'Родительский code',
                        "nullable" => true,
                        'example' => 'code',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'товар',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'т1',
                    ],
                    "code" => [
                        "type" => 'string',
                        "comment" => 'Код номенклатуры',
                        "nullable" => true,
                        'example' => '87',
                    ],
                    "type_good" => [
                        "type" => 'integer',
                        "comment" => 'Тип номенклатуры',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "type_unit" => [
                        "type" => 'integer',
                        "comment" => 'Единица измерения',
                        "nullable" => true,
                        'example' => '10',
                    ],
                    "barcodes" => [
                        "type" => 'json',
                        "comment" => 'Штрихкоды',
                        "nullable" => true,
                        'example' => '10123456',
                    ],
                    "is_from_external" => [
                        "type" => 'integer',
                        "comment" => 'Создана из внешнего источника',
                        "default" => 2,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "is_category" => [
                        "type" => 'integer',
                        "comment" => 'Является категорией',
                        "default" => 2,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/goods/goodcard') {
            $model = 'GoodCard';
            $table = "good_cards";
            $titleMenu = 'Карточка товара';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "карточке товара",   // Измененяет информацию о
                    "many_r" => "карточек товара", // возвращает список
                    "one" => "карточку товара",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Молоко',
                    ],
                    "price" => [
                        "type" => 'float',
                        "comment" => 'Цена',
                        "nullable" => true,
                        'example' => '150.00',
                    ],
                    "price_old" => [
                        "type" => 'float',
                        "comment" => 'Цена старая',
                        "nullable" => true,
                        'example' => '120.00',
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Полное описание',
                        "nullable" => true,
                        'example' => 'товар 1',
                    ],
                    "short_descr" => [
                        "type" => 'string',
                        "comment" => 'Короткое описание',
                        "nullable" => true,
                        'example' => 'т1',
                    ],
                    "img" => [
                        "type" => 'integer',
                        "comment" => 'ИД картинки',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "external_img" => [
                        "type" => 'json',
                        "comment" => 'Массив ссылок на внешние картинки',
                        "nullable" => true,
                        'example' => '["img.jpg", "img2.jpg"]',
                    ],
                    "price_х_unit" => [
                        "type" => 'float',
                        "comment" => 'Цена за 1 ед.',
                        "nullable" => true,
                        'example' => '50.00',
                    ],
                    "unit_id" => [
                        "type" => 'integer',
                        "comment" => 'Единица измерения',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "cnt_unit" => [
                        "type" => 'float',
                        "comment" => 'Кол-во единиц',
                        "default" => 1,
                        "nullable" => true,
                        'example' => '10',
                    ],
                    "articul" => [
                        "type" => 'string',
                        "comment" => 'Артикул',
                        "nullable" => true,
                        'example' => 'a88',
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Исходные данные',
                        "nullable" => true,
                        'example' => '["img.jpg", "img2.jpg"]',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/goods/typegood') {
            $model = 'TypeGood';
            $table = "type_goods";
            $titleMenu = 'Тип товара';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе товара",   // Измененяет информацию о
                    "many_r" => "типов товара", // возвращает список
                    "one" => "тип товара",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'бакалея',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'bakalea',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/goods/unitgood') {
            $model = 'UnitGood';
            $table = "unit_goods";
            $titleMenu = 'Единицы измерения';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "единице измерения",// Измененяет информацию о
                    "many_r" => "единиц измерения",// возвращает список
                    "one" => "единицу измерения",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'штуки',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое описание',
                        "nullable" => true,
                        'example' => 'sht',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function chat()
    {
        if ($this->string == '/chat/category') {
            $model = 'Category';
            $table = "category";
            $titleMenu = 'Категории';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "категории",   // Измененяет информацию о
                    "many_r" => "категорий", // возвращает список
                    "one" => "категорию",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "Плохое обслуживание"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        "example" => "bad"
                    ],
                    "color" => [
                        "type" => 'string',
                        "comment" => 'Цвет ярлыка',
                        "nullable" => true,
                        "example" => "red"
                    ],
                    "email" => [
                        "type" => 'string',
                        "comment" => 'Email для уведомления',
                        "nullable" => true,
                        "example" => "vasya@pupkin.ru"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'ID ответственного',
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
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/automessage') {
            $model = 'AutoMessage';
            $table = "auto_message";
            $titleMenu = 'Авто-сообщения';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "авто сообщении",   // Измененяет информацию о
                    "many_r" => "авто сообщений", // возвращает список
                    "one" => "авто сообщение",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "Приветствие"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        "example" => "hello"
                    ],
                    "message" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                        "example" => "Привет"
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "trigger_id" => [
                        "type" => 'integer',
                        "comment" => 'Триггер',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_work_time" => [
                        "type" => 'integer',
                        "comment" => 'Отправка в рабочее время',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "timetable" => [
                        "type" => 'json',
                        "comment" => 'Расписание',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                        "example" => "+79161234567"
                    ],
                    "email" => [
                        "type" => 'string',
                        "comment" => 'Email для уведомления',
                        "nullable" => true,
                        "example" => "vasya@pupkin.ru"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/blacklist') {
            $model = 'Blacklist';
            $table = "blacklist";
            $titleMenu = 'Черный список';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "заблокированных пользователях",   // Измененяет информацию о
                    "many_r" => "заблокированных пользователей", // возвращает список
                    "one" => "заблокированного пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "дурак"
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'Клиент',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "reason" => [
                        "type" => 'string',
                        "comment" => 'Причина',
                        "nullable" => true,
                        "example" => "ругается матом"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/chat') {
            $model = 'Chat';
            $table = "chat";
            $titleMenu = 'Чаты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "чате",   // Измененяет информацию о
                    "many_r" => "чатов", // возвращает список
                    "one" => "чат",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_read" => [
                        "type" => 'integer',
                        "comment" => 'Прочитано оператором',
                        "default" => 2,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_pinned" => [
                        "type" => 'integer',
                        "comment" => 'Закреплен',
                        "default" => 2,
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

                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/message') {
            $model = 'Message';
            $table = "messages";
            $titleMenu = 'Сообщения';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сообщении",   // Измененяет информацию о
                    "many_r" => "сообщений", // возвращает список
                    "one" => "сообщение",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название сообщения"
                    ],
                    "operator_id" => [
                        "type" => 'integer',
                        "comment" => 'Оператор',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'Клиент',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "session_id" => [
                        "type" => 'integer',
                        "comment" => 'Сессия',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "message" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                        "example" => "текст сообщения"
                    ],
                    "attachments" => [
                        "type" => 'json',
                        "comment" => 'Вложения',
                        "nullable" => true,
                        "example" => "файл.pdf"
                    ],
                    "links" => [
                        "type" => 'json',
                        "comment" => 'Ссылки',
                        "nullable" => true,
                        "example" => "//google.com"
                    ],
                    "is_our" => [
                        "type" => 'integer',
                        "comment" => 'Направление сообщения 1-исходящее от нас, 2-входящее от клиента',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_edited" => [
                        "type" => 'integer',
                        "comment" => 'Сообщение было отредактировано',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус', //'0-новое,1-прочитано,2-блокировано,4-системное',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],

                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/command') {
            $model = 'Command';
            $table = "commands";
            $titleMenu = 'Команды';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "команде",   // Измененяет информацию о
                    "many_r" => "команд", // возвращает список
                    "one" => "команду",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название команды"
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "default" => 'Эта команда делает...',
                        "nullable" => true,
                        "example" => "команда редактирования"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/grouphashtag') {
            $model = 'GroupHashtag';
            $table = "hashtag_groups";
            $titleMenu = 'Группы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "группе",   // Измененяет информацию о
                    "many_r" => "групп", // возвращает список
                    "one" => "групу",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название группы"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Служебное название',
                        "nullable" => true,
                        "example" => "группа сообщений"
                    ],
                    "is_for_operators" => [
                        "type" => 'integer',
                        "comment" => 'Для операторов: 1-да,2-нет',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "default" => 'Эта группа для...',
                        "nullable" => true,
                        "example" => "группа сообщений"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/groupquickanswer') {
            $model = 'GroupQuickAnswer';
            $table = "quick_answer_groups";
            $titleMenu = 'Группы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "группе",   // Измененяет информацию о
                    "many_r" => "групп", // возвращает список
                    "one" => "групу",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название группы"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        "example" => "группа тех поддержки"
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "default" => 'Эта группа для...',
                        "nullable" => true,
                        "example" => "Это группа для общения"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/hashtag') {
            $model = 'Hashtag';
            $table = "hashtag";
            $titleMenu = 'Хештеги';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "хештеге",   // Измененяет информацию о
                    "many_r" => "хештегов", // возвращает список
                    "one" => "хештег",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название хештега"
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "group_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа хештегов',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_for_operators" => [
                        "type" => 'integer',
                        "comment" => 'Для операторов: 1-да,2-нет',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "color" => [
                        "type" => 'string',
                        "comment" => 'Цвет',
                        "nullable" => true,
                        "example" => "зеленый"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/operator') {
            $model = 'Operator';
            $table = "operators";
            $titleMenu = 'Операторы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "операторе",   // Измененяет информацию о
                    "many_r" => "операторов", // возвращает список
                    "one" => "оператора",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/quickanswer') {
            $model = 'QuickAnswer';
            $table = "quick_answers";
            $titleMenu = 'Быстрые ответы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "быстрых ответах",   // Измененяет информацию о
                    "many_r" => "быстрых ответов", // возвращает список
                    "one" => "быстрые ответы",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "название"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Команда',
                        "nullable" => true,
                        "example" => "команда х"
                    ],
                    "message" => [
                        "type" => 'text',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                        "example" => "сообщение"
                    ],
                    "group_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "operator_id" => [
                        "type" => 'integer',
                        "comment" => 'Оператор',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_private" => [
                        "type" => 'boolean',
                        "comment" => 'Приватность',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/session') {
            $model = 'Session';
            $table = "session";
            $titleMenu = 'Сессии';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сессии",   // Измененяет информацию о
                    "many_r" => "сессий", // возвращает список
                    "one" => "сессии",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Номер сессии',
                        "nullable" => true,
                        "example" => "123"
                    ],
                    "subject" => [
                        "type" => 'string',
                        "comment" => 'Тема обращения',
                        "nullable" => true,
                        "example" => "тема обращения"
                    ],
                    "type_ask_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип обращения',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'Клиент',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "operator_id" => [
                        "type" => 'integer',
                        "comment" => 'Оператор',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_read" => [
                        "type" => 'integer',
                        "comment" => 'Прочитано оператором',
                        "default" => 2,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_pinned" => [
                        "type" => 'integer',
                        "comment" => 'Закреплено',
                        "default" => 2,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "last_message_from_client_id" => [
                        "type" => 'integer',
                        "comment" => 'Последнее сообщения от клиента',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "last_message_from_client" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата и время последнего сообщения от клиента',
                        "nullable" => true,
                        "example" => "25.01.2022 12:00"
                    ],
                    "last_message_from_operator_id" => [
                        "type" => 'integer',
                        "comment" => 'Последнее сообщения от оператора',
                        "nullable" => true,
                        "example" => "2"
                    ],
                    "last_message_from_operator" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата и время последнего сообщения от оператора',
                        "nullable" => true,
                        "example" => "26.01.2022 12:00"
                    ],
                    "last_read_from_operator" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата и время чтения последнего сообщения от оператора',
                        "nullable" => true,
                        "example" => "26.01.2022 12:00"
                    ],
                    "time_reaction" => [
                        "type" => 'integer',
                        "comment" => 'Время реагирования',
                        "nullable" => true,
                        "example" => "10"
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "location_id" => [
                        "type" => 'integer',
                        "comment" => 'Локация',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус 0-new,1-active,2-block,3-login started,4-archived',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/sessionhashtag') {
            $model = 'SessionHashtag';
            $table = "session_hashtags";
            $titleMenu = 'Сессии хештегов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "хештеге",   // Измененяет информацию о
                    "many_r" => "хештегов", // возвращает список
                    "one" => "хештег",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "123"
                    ],
                    "session_id" => [
                        "type" => 'integer',
                        "comment" => 'Сессия',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "hashtag_id" => [
                        "type" => 'integer',
                        "comment" => 'Хештег',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/setting') {
            $model = 'Setting';
            $table = "settings";
            $titleMenu = 'Настройки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "настройке",   // Измененяет информацию о
                    "many_r" => "настроек", // возвращает список
                    "one" => "настройку",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "удаление"
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "nullable" => true,
                        "example" => "описание настройки"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "val" => [
                        "type" => 'string',
                        "comment" => 'Значение',
                        "nullable" => true,
                        "example" => "удаление"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/typeask') {
            $model = 'TypeAsk';
            $table = "types";
            $titleMenu = 'Типы обращений';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе обращения",   // Измененяет информацию о
                    "many_r" => "типов обращений", // возвращает список
                    "one" => "тип обращения",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "тип обращения"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Служебное название',
                        "nullable" => true,
                        "example" => "название"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/chat/worktime') {
            $model = 'WorkTime';
            $table = "worktime";
            $titleMenu = 'Время работы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "времени работы",   // Измененяет информацию о
                    "many_r" => "времени работ", // возвращает список
                    "one" => "время работы",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        "example" => "работы"
                    ],
                    "is_work" => [
                        "type" => 'integer',
                        "comment" => 'Рабочее',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "time_from" => [
                        "type" => 'time',
                        "comment" => 'Время начала',
                        "nullable" => true,
                        "example" => "11:00"
                    ],
                    "time_to" => [
                        "type" => 'time',
                        "comment" => 'Время окончания',
                        "nullable" => true,
                        "example" => "17:00"
                    ],
                    "automessage_id" => [
                        "type" => 'integer',
                        "comment" => 'Автосообщение',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус 0-новое,1-прочитано,2-блокировано',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function address()
    {
        if ($this->string == '/address/staff') {
            $model = 'Staff';
            $table = "staff";
            $titleMenu = 'Сотрудники';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сотрудниках",   // Измененяет информацию о
                    "many_r" => "сотрудников", // возвращает список
                    "one" => "сотрудника",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'ФИО',
                        'example' => 'Иванов Иван',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "example" => "ivanov"
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "location_id" => [
                        "type" => 'integer',
                        "comment" => 'Локация',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "pin" => [
                        "type" => 'string',
                        "comment" => 'Пин',
                        "nullable" => true,
                        'example' => '1234',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------


    }

    public function common()
    {
        if ($this->string == '/common/audience') {
            $model = 'Audience';
            $table = "audience";
            $titleMenu = 'Аудитории';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "аудитории",   // Измененяет информацию о
                    "many_r" => "аудиторий", // возвращает список
                    "one" => "аудиторию",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'name1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "example" => "name"
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Параметры сегментации',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "lists" => [
                        "type" => 'json',
                        "comment" => 'Белый и черный списки пользователей',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "is_internal" => [
                        "type" => 'integer',
                        "comment" => 'Не выводить в общем списке',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/common/audienceuser') {
            $model = 'AudienceUser';
            $table = "audience_user";
            $titleMenu = 'Сегментации пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сегментации пользователей",   // Измененяет информацию о
                    "many_r" => "сегментаций пользователей", // возвращает список
                    "one" => "сегментацию пользователей",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "audience_id" => [
                        "type" => 'integer',
                        "comment" => 'Аудитория',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/common/job') {
            $model = 'Job';
            $table = "job";
            $titleMenu = 'Задачи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "задаче",   // Измененяет информацию о
                    "many_r" => "задач", // возвращает список
                    "one" => "задачу",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Класс',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "uuid" => [
                        "type" => 'string',
                        "comment" => 'UUID',
                        "nullable" => true,
                        'example' => '12345',
                    ],
                    "src" => [
                        "type" => 'jsonb',
                        "comment" => 'Параметры',
                        "nullable" => true,
                        'example' => '[1,4]',
                    ],
                    "res" => [
                        "type" => 'jsonb',
                        "comment" => 'Результат',
                        "nullable" => true,
                        'example' => '[1]',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/common/pin') {
            $model = 'Pin';
            $table = "pins";
            $titleMenu = 'Пин коды';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "пин коде",   // Измененяет информацию о
                    "many_r" => "пин кодов", // возвращает список
                    "one" => "пин код",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Номер телефона',
                        'example' => '+12999999999',
                    ],
                    "pin" => [
                        "type" => 'string',
                        "comment" => 'PIN',
                        'example' => '12345',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/common/smsgate') {
            $model = 'SMSGate';
            $table = "sms_gates";
            $titleMenu = 'Sms';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "sms",   // Измененяет информацию о
                    "many_r" => "sms", // возвращает список
                    "one" => "sms",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        'example' => 'name',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "nullable" => true,
                        'example' => 'nm',
                    ],
                    "options" => [
                        "type" => 'json',
                        "comment" => 'Доступные опции',
                        "nullable" => true,
                        'example' => '[1,3]',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function video()
    {
        if ($this->string == '/video/services') {
            $model = 'Service';
            $table = "service";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервисе",   // Измененяет информацию о
                    "many_r" => "сервисов", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги - типы сервисов',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/video/hostings') {
            $model = 'Hosting';
            $table = "hosting";
            $titleMenu = 'Хостинги';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "хостинге",   // Измененяет информацию о
                    "many_r" => "хостингов", // возвращает список
                    "one" => "хостинг",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Kinescope',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'kinescope',
                    ],
                    "params" => [
                        "type" => 'json',
                        "comment" => 'Параметры',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ]
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/video/files') {
            $model = 'VideoFile';
            $table = "videofile";
            $titleMenu = 'Файлы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "файле",   // Измененяет информацию о
                    "many_r" => "файлов", // возвращает список
                    "one" => "файл",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'video.mp4',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'multfilm-pro-snegurochku',
                    ],
                    "hosting_id" => [
                        "type" => 'string',
                        "comment" => 'Хостинг',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "path" => [
                        "type" => 'string',
                        "comment" => 'Путь',
                        "nullable" => true,
                        'example' => '/2023/01/video.mp4',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ]
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/video/profiles') {
            $model = 'Profile';
            $table = "profiles";
            $titleMenu = 'Профили';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "профиле",   // Измененяет информацию о
                    "many_r" => "профилей", // возвращает список
                    "one" => "профиль",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Созвон',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'call',
                    ],
                    "hosting_id" => [
                        "type" => 'string',
                        "comment" => 'Хостинг',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "params" => [
                        "type" => 'json',
                        "comment" => 'Параметры',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ]
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/video/live') {
            $model = 'Live';
            $table = "live";
            $titleMenu = 'Живые потоки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "потоке",   // Измененяет информацию о
                    "many_r" => "потоков", // возвращает список
                    "one" => "поток",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Вещание',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'call',
                    ],
                    "hosting_id" => [
                        "type" => 'string',
                        "comment" => 'Хостинг',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "params" => [
                        "type" => 'json',
                        "comment" => 'Параметры',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ]
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function calls()
    {
        //----------------------------------
        if ($this->string = '/calls/user_videos') {
            $model = 'UserVideo';
            $table = "user_videos";
            $titleMenu = 'Видео пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "потоке",   // Измененяет информацию о
                    "many_r" => "потоков", // возвращает список
                    "one" => "поток",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "videofile_id" => [
                        "type" => 'integer',
                        "comment" => 'Видео',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ]
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/calls/user_streams') {
            $model = 'UserStream';
            $table = "user_streams";
            $titleMenu = 'Потоки пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "потоке",   // Измененяет информацию о
                    "many_r" => "потоков", // возвращает список
                    "one" => "поток",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Вещание',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'call',
                    ],
                    "stream_id" => [
                        "type" => 'integer',
                        "comment" => 'Поток',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "external_id" => [
                        "type" => 'integer',
                        "comment" => 'Внешний пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "session" => [
                        "type" => 'string',
                        "comment" => 'Сессия',
                        "nullable" => true,
                        'example' => 'TX-1286',
                    ],
                    "params" => [
                        "type" => 'json',
                        "comment" => 'Параметры',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ]
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function main()
    {
        if ($this->string == '/main/client') {
            $model = 'Client';
            $table = "clients";
            $titleMenu = 'Клиенты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "клиенте",   // Измененяет информацию о
                    "many_r" => "клиентов", // возвращает список
                    "one" => "клиента",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'клиент',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        'example' => '1',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "is_registered_in_app" => [
                        "type" => 'integer',
                        "comment" => 'Зарегистрирован в приложении',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Количество покупок',
                        "default" => 0,
                        'example' => '2',
                    ],
                    "date_first_buy" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата первой покупки',
                        "nullable" => true,
                        'example' => '2023-01-01 00:00:00',
                    ],
                    "sum_first_buy" => [
                        "type" => 'integer',
                        "comment" => 'Сумма первой покупки',
                        "default" => 0,
                        'example' => '100',
                    ],
                    "sum_total" => [
                        "type" => 'integer',
                        "comment" => 'Сумма всего потрачено',
                        "default" => 0,
                        'example' => '150',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "date_birth_in_this_year" => [
                        "type" => 'date',
                        "comment" => 'День рождения в этом году',
                        "nullable" => true,
                        'example' => '2023-08-01',
                    ],
                    "cheque_avg" => [
                        "type" => 'integer',
                        "comment" => 'Средний чек',
                        "nullable" => true,
                        'example' => '100',
                    ],
                    "days_with_our" => [
                        "type" => 'integer',
                        "comment" => 'Дней с регистрации',
                        "nullable" => true,
                        'example' => '25',
                    ],
                    "days_with_buy" => [
                        "type" => 'integer',
                        "comment" => 'Дней с покупками',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "freq_id" => [
                        "type" => 'integer',
                        "comment" => 'Частота покупок',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '5',
                    ],
                    "is_buy_today" => [
                        "type" => 'boolean',
                        "comment" => 'Была ли покупкак сегодня',
                        "default" => false,
                        'example' => '5',
                    ],
                    "goods_categories" => [
                        "type" => 'jsonb',
                        "comment" => 'Категории товаров',
                        "nullable" => true,
                        'example' => '[1,2,3]',
                    ],
                    "goods" => [
                        "type" => 'jsonb',
                        "comment" => 'Товары',
                        "nullable" => true,
                        'example' => '[1,2,3]',
                    ],
                    "goods_cnt" => [
                        "type" => 'jsonb',
                        "comment" => 'Количество покупок товаров',
                        "nullable" => true,
                        'example' => '[1,2,3]',
                    ],
                    "goods_popular" => [
                        "type" => 'jsonb',
                        "comment" => 'Самые популярные товары',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "locations" => [
                        "type" => 'jsonb',
                        "comment" => 'Локации',
                        "nullable" => true,
                        'example' => '[123.456,789.012]',
                    ],
                    "regions" => [
                        "type" => 'jsonb',
                        "comment" => 'Регионы',
                        "nullable" => true,
                        'example' => '[Регион 1]',
                    ],
                    "businesses" => [
                        "type" => 'jsonb',
                        "comment" => 'Бизнесы',
                        "nullable" => true,
                        'example' => '[1,3]',
                    ],
                    "date_last_buy" => [
                        "type" => 'date',
                        "comment" => 'Дата последней покупки',
                        "nullable" => true,
                        'example' => '2023-01-01',
                    ],
                    "count_online_buy" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во онлайн покупок',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '2',
                    ],
                    "count_return" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во возвратов покупок',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "count_chat_sessionsn" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во обращений в ТП',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "count_active_chat_sessions" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во активных обращений в ТП',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/company') {
            $model = 'Company';
            $table = "companies";
            $titleMenu = 'Компании';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "компании",   // Измененяет информацию о
                    "many_r" => "компаний", // возвращает список
                    "one" => "компанию",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'Название компании',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Логин',
                        'example' => 'логин компании',
                    ],
                    "logo" => [
                        "type" => 'string',
                        "comment" => 'Логотип',
                        "nullable" => true,
                        'example' => 'logo.png',
                    ],
                    "account_image" => [
                        "type" => 'string',
                        "comment" => 'Изображение аккаунта',
                        "nullable" => true,
                        'example' => 'image.png',
                    ],
                    "fullname" => [
                        "type" => 'string',
                        "comment" => 'Полное название',
                        "nullable" => true,
                        'example' => 'полное название компании',
                    ],
                    "ogrn" => [
                        "type" => 'string',
                        "comment" => 'ОГРН',
                        "nullable" => true,
                        'example' => 'ОГРН',
                    ],
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                        'example' => '+170 898 55 555',
                    ],
                    "email" => [
                        "type" => 'string',
                        "comment" => 'email',
                        "nullable" => true,
                        'example' => 'company@cm.ru',
                    ],
                    "site" => [
                        "type" => 'string',
                        "comment" => 'Сайт',
                        "nullable" => true,
                        'example' => '://company.loc',
                    ],
                    "inn" => [
                        "type" => 'string',
                        "comment" => 'ИНН',
                        "nullable" => true,
                        'example' => '21233987655',
                    ],
                    "kpp" => [
                        "type" => 'string',
                        "comment" => 'КПП',
                        "nullable" => true,
                        'example' => 'кпп',
                    ],
                    "okpo" => [
                        "type" => 'string',
                        "comment" => 'ОКПО',
                        "nullable" => true,
                        'example' => 'окпо',
                    ],
                    "director_fio" => [
                        "type" => 'string',
                        "comment" => 'ФИО директора',
                        "nullable" => true,
                        'example' => 'Иванов Иван Иванович',
                    ],
                    "director_position" => [
                        "type" => 'string',
                        "comment" => 'Должность директора',
                        "nullable" => true,
                        'example' => 'директор',
                    ],
                    "company_src" => [
                        "type" => 'json',
                        "comment" => 'Данные по компании',
                        "nullable" => true,
                        'example' => '[данные1, данные2]',
                    ],
                    "bank_src" => [
                        "type" => 'json',
                        "comment" => 'Данные по банку',
                        "nullable" => true,
                        'example' => '[данные1, данные2]',
                    ],
                    "bank" => [
                        "type" => 'string',
                        "comment" => 'Банк',
                        "nullable" => true,
                        'example' => 'Тинькофф',
                    ],
                    "bik" => [
                        "type" => 'string',
                        "comment" => 'БИК',
                        "nullable" => true,
                        'example' => 'TNF9055',
                    ],
                    "korr_schet" => [
                        "type" => 'string',
                        "comment" => 'Корр. счет',
                        "nullable" => true,
                        'example' => 'T8704355F9000',
                    ],
                    "rasch_schet" => [
                        "type" => 'string',
                        "comment" => 'Расч. счет',
                        "nullable" => true,
                        'example' => 'RS2355545199044',
                    ],
                    "url_appstore" => [
                        "type" => 'string',
                        "comment" => 'URL приложения в аппсторе',
                        "nullable" => true,
                        'example' => '//appstore.com',
                    ],
                    "url_playmarket" => [
                        "type" => 'string',
                        "comment" => 'URL приложения в Google play',
                        "nullable" => true,
                        'example' => '//google.play.com',
                    ],
                    "timezone" => [
                        "type" => 'string',
                        "comment" => 'Часовой пояс',
                        "nullable" => true,
                        'example' => 'UTC',
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Локализация',
                        "nullable" => true,
                        'example' => '[en,ru]',
                    ],
                    "lang" => [
                        "type" => 'string',
                        "comment" => 'Язык приложения',
                        "nullable" => true,
                        'example' => 'ru',
                    ],
                    "valute_code" => [
                        "type" => 'string',
                        "comment" => 'Код валюты',
                        "nullable" => true,
                        'example' => 'RUB',
                    ],
                    "legal_address_id" => [
                        "type" => 'integer',
                        "comment" => 'Юридический адрес',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "fact_address_id" => [
                        "type" => 'integer',
                        "comment" => 'Фактический адрес',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => '0-новая,1-активна,2-блокирована',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/companycontact') {
            $model = 'CompanyContact';
            $table = "company_contacts";
            $titleMenu = 'Контакты компании';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "контактах компании",   // Измененяет информацию о
                    "many_r" => "контактов компании", // возвращает список
                    "one" => "контакты компании",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'Название контактов компании',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        'example' => 'Короткое название контактов компании',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "example" => "1"
                    ],
                    "val" => [
                        "type" => 'string',
                        "comment" => 'Значение',
                        "nullable" => true,
                        "example" => "contact",
                    ],
                    "file" => [
                        "type" => 'string',
                        "comment" => 'Файл',
                        "nullable" => true,
                        "example" => "contact",
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/device') {
            $model = 'Device';
            $table = "devices";
            $titleMenu = 'Устройства';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "устройстве",   // Измененяет информацию о
                    "many_r" => "устройств", // возвращает список
                    "one" => "устройство",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'Название устройства',
                    ],
                    "imei" => [
                        "type" => 'string',
                        "comment" => 'IMEI',
                        'example' => '123456789012345',
                    ],
                    "os" => [
                        "type" => 'string',
                        "comment" => 'Операционная система',
                        "nullable" => true,
                        "example" => "ios"
                    ],
                    "version" => [
                        "type" => 'string',
                        "comment" => 'Версия ОС',
                        "nullable" => true,
                        "example" => "15.0"
                    ],
                    "brand" => [
                        "type" => 'string',
                        "comment" => 'Бренд',
                        "nullable" => true,
                        "example" => "Apple"
                    ],
                    "model" => [
                        "type" => 'string',
                        "comment" => 'Модель',
                        "nullable" => true,
                        "example" => "iPhone"
                    ],
                    "firebase_device_id" => [
                        "type" => 'string',
                        "comment" => 'ИД устройства для Firebase',
                        "nullable" => true,
                        "example" => "88345"
                    ],
                    "geo" => [
                        "type" => 'geometry',
                        "comment" => 'Координаты',
                        "nullable" => true,
                        "example" => "POINT (48.8588443 2.2943506)"
                    ],
                    "date_geo" => [
                        "type" => 'timestamp',
                        "comment" => 'Время съема координат',
                        "nullable" => true,
                        "example" => "2023-05-07 12:00:00"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => "1",
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/file') {
            $model = 'File';
            $table = "files";
            $titleMenu = 'Файлы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "файле",   // Измененяет информацию о
                    "many_r" => "файлов", // возвращает список
                    "one" => "файл",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название файла',
                    ],
                    "path" => [
                        "type" => 'string',
                        "comment" => 'Путь',
                        "nullable" => true,
                        'example' => '/path/to/file',
                    ],
                    "category" => [
                        "type" => 'string',
                        "comment" => 'Категория',
                        "nullable" => true,
                        'example' => 'image',
                    ],
                    "size" => [
                        "type" => 'integer',
                        "comment" => 'Размер',
                        "nullable" => true,
                        "example" => "12мб"
                    ],
                    "ext" => [
                        "type" => 'string',
                        "comment" => 'Расширение',
                        "nullable" => true,
                        "example" => "png"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "2"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус 0-новый,1-активный,2-блокирован',
                        "default" => "0",
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/frequency') {
            $model = 'Frequency';
            $table = "frequency";
            $titleMenu = 'Частота покупок';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "Частоте покупок",   // Измененяет информацию о
                    "many_r" => "Частоты покупок", // возвращает список
                    "one" => "Частоту покупок",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название частоты покупок',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'частота',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => "1",
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/log') {
            $model = 'Log';
            $table = "logs";
            $titleMenu = 'Логи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "логе",   // Измененяет информацию о
                    "many_r" => "логов", // возвращает список
                    "one" => "лог",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "description" => [
                        "type" => 'text',
                        "nullable" => true,
                        'example' => 'text',
                    ],
                    "origin" => [
                        "type" => 'string',
                        "nullable" => true,
                        "length" => 200,
                        'example' => 'origin',
                    ],
                    "type" => [
                        "type" => 'enum',
                        "values" => ['log', 'store', 'change', 'delete'],
                        'example' => 'log',
                    ],
                    "result" => [
                        "type" => 'enum',
                        "values" => ['success', 'neutral', 'failure'],
                        'example' => 'success',
                    ],
                    "level" => [
                        "type" => 'enum',
                        "values" => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
                        'example' => 'emergency',
                    ],
                    "context" => [
                        "type" => 'text',
                        "nullable" => true,
                        'example' => 'context',
                    ],
                    "extra" => [
                        "type" => 'text',
                        "nullable" => true,
                        'example' => 'extra',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "ip" => [
                        "type" => 'ipAddress',
                        "nullable" => true,
                        'example' => '120.0.0.1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => "1",
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------

        //----------------------------------
        if ($this->string == '/main/permission') {
            $model = 'Permission';
            $table = "permissions";
            $titleMenu = 'Права доступа';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "праве доступа",   // Измененяет информацию о
                    "many_r" => "прав доступа", // возвращает список
                    "one" => "право доступа",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        'example' => 'view',
                    ],
                    "slug" => [
                        "type" => 'string',
                        "comment" => 'Служебное',
                        "unique" => true,
                        'example' => 'slug',
                    ],
                    "resource" => [
                        "type" => 'string',
                        "comment" => 'Ресурс',
                        "default" => 'system',
                        'example' => 'system',
                    ],
                    "system" => [
                        "type" => 'boolean',
                        "comment" => 'Системное',
                        "default" => 0,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "1"
                    ],
                    "module_id" => [
                        "type" => 'integer',
                        "comment" => 'Модуль',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "feature_id" => [
                        "type" => 'integer',
                        "comment" => 'Фича',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/permissionrole') {
            $model = 'PermissionRole';
            $table = "permission_role";
            $titleMenu = 'права ролей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "праве доступа для роли",   // Измененяет информацию о
                    "many_r" => "прав доступа для ролей", // возвращает список
                    "one" => "право доступа для роли",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "permission_id" => [
                        "type" => 'integer',
                        "example" => "1"
                    ],
                    "role_id" => [
                        "type" => 'integer',
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/permissionuser') {
            $model = 'PermissionUser';
            $table = "permission_user";
            $titleMenu = 'Права пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "праве пользователя",   // Измененяет информацию о
                    "many_r" => "прав пользователей", // возвращает список
                    "one" => "право пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "permission_id" => [
                        "type" => 'integer',
                        "example" => "1"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/role') {
            $model = 'Role';
            $table = "roles";
            $titleMenu = 'Роли';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "роле",   // Измененяет информацию о
                    "many_r" => "ролей", // возвращает список
                    "one" => "роль",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        'example' => 'Администратор',
                    ],
                    "slug" => [
                        "type" => 'string',
                        "unique" => true,
                        'example' => 'админ',
                    ],
                    "description" => [
                        "type" => 'text',
                        "nullable" => true,
                        'example' => 'desc',
                    ],
                    "system" => [
                        "type" => 'boolean',
                        "default" => 0,
                        'example' => '1',
                    ],
                    "tags" => [
                        "type" => 'json',
                        "nullable" => true,
                        'example' => '[tags1,tags2]',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/roleuser') {
            $model = 'RoleUser';
            $table = "role_user";
            $titleMenu = 'Роли пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "роле пользователя",   // Измененяет информацию о
                    "many_r" => "ролей пользователя", // возвращает список
                    "one" => "роль пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "role_id" => [
                        "type" => 'integer',
                        "comment" => 'Роль',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/setting') {
            $model = 'Setting';
            $table = "settings";
            $titleMenu = 'Настройки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "настройке",   // Измененяет информацию о
                    "many_r" => "настроек", // возвращает список
                    "one" => "настройку",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название настройки',
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'описание настройки',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "program_id" => [
                        "type" => 'integer',
                        "comment" => 'Программа лояльности',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "val" => [
                        "type" => 'string',
                        "comment" => 'Значение',
                        "nullable" => true,
                        'example' => 'значение настройки',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new,1-active,2-blocked',
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/trigger') {
            $model = 'Trigger';
            $table = "trigger";
            $titleMenu = 'Триггеры';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "триггере",   // Измененяет информацию о
                    "many_r" => "триггеров", // возвращает список
                    "one" => "триггер",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название триггера',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое',
                        "nullable" => true,
                        'example' => 'триггер',
                    ],
                    "event" => [
                        "type" => 'string',
                        "comment" => 'Класс события',
                        "nullable" => true,
                        'example' => 'Events',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги',
                        "default" => json_encode(['main']),
                        'example' => 'Events',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/user') {
            $model = 'User';
            $table = "users";
            $titleMenu = 'Пользователи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "пользователе",   // Измененяет информацию о
                    "many_r" => "пользователей", // возвращает список
                    "one" => "пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "nick" => [
                        "type" => 'string',
                        "comment" => 'Имя в приложении',
                        "nullable" => true,
                        'example' => 'ник',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Имя',
                        "nullable" => true,
                        'example' => 'Иван',
                    ],
                    "middle_name" => [
                        "type" => 'string',
                        "comment" => 'Отчество',
                        "nullable" => true,
                        'example' => 'Иванович',
                    ],
                    "last_name" => [
                        "type" => 'string',
                        "comment" => 'Фамилия',
                        "nullable" => true,
                        'example' => 'Иванов',
                    ],
                    "birthday" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата рождения',
                        "nullable" => true,
                        'example' => '1990-01-01',
                    ],
                    "sex" => [
                        "type" => 'string',
                        "comment" => 'Пол: M или F',
                        "nullable" => true,
                        "default" => 'M',
                        'example' => 'M',
                    ],
                    "email" => [
                        "type" => 'string',
                        "comment" => 'Емайл',
                        "nullable" => true,
                        "unique" => true,
                        'example' => 'ivan@mail.ru',
                    ],
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                        "unique" => true,
                        'example' => '+170 341 235 555',
                    ],
                    "password" => [
                        "type" => 'string',
                        "comment" => 'Пароль',
                        "nullable" => true,
                        'example' => 'pass_123',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "region_id" => [
                        "type" => 'integer',
                        "comment" => 'Регион',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "owner" => [
                        "type" => 'integer',
                        "comment" => 'Владелец',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "email_verified_at" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата верификации емайл',
                        "nullable" => true,
                        'example' => '2023-01-01 12:00:00',
                    ],
                    "last_login_at" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата последнего входа',
                        "nullable" => true,
                        'example' => '2023-01-05 12:00:00',
                    ],
                    "lang" => [
                        "type" => 'string',
                        "comment" => 'Язык',
                        "nullable" => true,
                        "default" => 'ru',
                        'example' => 'en',
                    ],
                    "age" => [
                        "type" => 'integer',
                        "comment" => 'Возраст',
                        "nullable" => true,
                        'example' => '22',
                    ],
                    "city_id" => [
                        "type" => 'integer',
                        "comment" => 'Город',
                        "nullable" => true,
                        "onDelete" => 'cascade',
                        'example' => '2',
                    ],
                    "country_id" => [
                        "type" => 'integer',
                        "comment" => 'Страна',
                        "nullable" => true,
                        "onDelete" => 'cascade',
                        'example' => '3',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус 0-new,1-active,2-block,3-login started,4-archived',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "0"
                    ],
                    "is_admin_added" => [
                        "type" => 'integer',
                        "comment" => 'Добавлен админом',
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/main/userdevice') {
            $model = 'UserDevice';
            $table = "user_devices";
            $titleMenu = 'Устройства пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "устройстве пользователя",   // Измененяет информацию о
                    "many_r" => "устройств пользователей", // возвращает список
                    "one" => "устройство пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "device_id" => [
                        "type" => 'integer',
                        "comment" => 'Устройство',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function marketing()
    {
        if ($this->string == '/marketing/answer') {
            $model = 'Answer';
            $table = "answer";
            $titleMenu = 'Ответы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "ответе", // Измененяет информацию о
                    "many_r" => "ответов",// возвращает список
                    "one" => "ответ",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "question_id" => [
                        "type" => 'integer',
                        "comment" => 'Вопрос',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "campaign_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/campaign') {
            $model = 'Campaign';
            $table = "campaign";
            $titleMenu = 'Кампании';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "рекламной кампании",   // Измененяет информацию о
                    "many_r" => "рекламных кампаний", // возвращает список
                    "one" => "рекламную кампанию",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Купи слона',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'kupi_slona',
                    ],
                    "name_for_marketolog" => [
                        "type" => 'string',
                        "comment" => 'Название для маркетолога',
                        "nullable" => true,
                        'example' => 'Купи слона за 500р',
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'Акция по покупке слона',
                    ],
                    "short_descr" => [
                        "type" => 'text',
                        "comment" => 'Короткое описание',
                        "nullable" => true,
                        'example' => 'Слон',
                    ],
                    "link" => [
                        "type" => 'string',
                        "comment" => 'Ссылка',
                        "nullable" => true,
                        'example' => 'https://test.com',
                    ],
                    "link_app" => [
                        "type" => 'string',
                        "comment" => 'Переход на страницу приложения',
                        "nullable" => true,
                        'example' => 'https://play.google.com/aaaa',
                    ],
                    "img" => [
                        "type" => 'integer',
                        "comment" => 'Картинка',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "is_show_accumulation_indicator" => [
                        "type" => 'integer',
                        "comment" => 'Отображать индикатор накопления',
                        "nullable" => true,
                        "default" => 2,
                        'example' => '2',
                    ],
                    "type_activation_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип активации',
                        "default" => 1,
                        'example' => '2',
                    ],
                    "type_win_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип вознаграждения',
                        "default" => 1,
                        'example' => '2',
                    ],
                    "kind_win_id" => [
                        "type" => 'integer',
                        "comment" => 'Вид вознаграждения',
                        "default" => 1,
                        'example' => '2',
                    ],
                    "is_show_history_accumulation" => [
                        "type" => 'integer',
                        "comment" => 'Отображать историю накоплений в рамках кампании',
                        "nullable" => true,
                        "default" => 2,
                        'example' => '2',
                    ],
                    "tags" => [
                        "type" => 'text',
                        "comment" => 'Тэги',
                        "nullable" => true,
                        'example' => 'slon',
                    ],
                    "group_campaign_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа кампаний',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "mechanic_id" => [
                        "type" => 'integer',
                        "comment" => 'Механика',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "link_campaign_id" => [
                        "type" => 'integer',
                        "comment" => 'Связанная рекламная кампания',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "audience_id" => [
                        "type" => 'integer',
                        "comment" => 'Аудитория',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "is_audience_new_only" => [
                        "type" => 'integer',
                        "comment" => 'Аудитория только новые',
                        "nullable" => true,
                        "default" => 2,
                        'example' => '2',
                    ],
                    "geography" => [
                        "type" => 'jsonb',
                        "comment" => 'География',
                        "nullable" => true,
                        "default" => json_encode([
                            "businesses_included" => "*",
                            "regions_included" => "*",
                            "locations_included" => "*",
                            "businesses_excluded" => [],
                            "regions_excluded" => [],
                            "locations_excluded" => []
                        ]),
                        'example' => '{businesses_included:*}',
                    ],
                    "params" => [
                        "type" => 'jsonb',
                        "comment" => 'Параметры механики',
                        "nullable" => true,
                        "default" => json_encode([
                            "points_from" => 200,
                            "points_to" => 250,
                            "sum_from" => 200,
                            "sum_to" => 250,
                            "type_buys" => "*",
                            "tags_excluded" => "",
                            "tags_included" => ['base'],
                            "users_included" => "*",
                            "min_points" => 80,
                            "min_sum" => 1000,
                            "cnt_int_day" => 1,
                            "goods_category_included" => json_encode([3]),
                            "goods_category_excluded" => '',
                            "goods_included" => json_encode([5, 12]),
                            "goods_excluded" => json_encode([9]),
                            "goods_cnt_from" => 1,
                            "goods_cnt_to" => 5,
                        ]),
                        'example' => '{points_from: 222}',
                    ],
                    "params_win" => [
                        "type" => 'json',
                        "comment" => 'Параметр вознаграждения',
                        "nullable" => true,
                        "default" => json_encode([
                            "points" => 78,
                            "percent_of_total" => 0,
                            "percent_of_goods" => 0,
                            "start_activity" => 0,
                            "end_activity" => 5,
                            "qrcode" => "123456",
                            "gift_id" => 0,
                            "coupon_id" => 0,
                            "ext_gift_id" => 2,
                            "level" => "middle",
                            "sale_sum" => 0,
                            "sale_percent" => 0
                        ]),
                        'example' => '{points_from: 222}',
                    ],
                    "is_available" => [
                        "type" => 'integer',
                        "comment" => 'Является доступной',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "is_active" => [
                        "type" => 'integer',
                        "comment" => 'Является активной',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "is_multi" => [
                        "type" => 'integer',
                        "comment" => 'Мульти',
                        "nullable" => true,
                        "default" => 2,
                        'example' => '2',
                    ],
                    "multi_cnt_available" => [
                        "type" => 'integer',
                        "comment" => 'Количество доступных использований',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "people_cnt_available" => [
                        "type" => 'integer',
                        "comment" => 'Количество доступных участников',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "people_cnt" => [
                        "type" => 'integer',
                        "comment" => 'Количество участников',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '2',
                    ],
                    "date_start" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата старта кампании',
                        "nullable" => true,
                        'example' => '2023-01-01 00:00:00',
                    ],
                    "date_end" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата окончания кампании',
                        "nullable" => true,
                        'example' => '2023-01-25 00:00:00',
                    ],
                    "timetable_id" => [
                        "type" => 'integer',
                        "comment" => 'Расписание работы счетчиков',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "push_settings" => [
                        "type" => 'jsonb',
                        "comment" => "Настройки пуш сообщений",
                        "default" => json_encode([
                            "new_campaign" => [
                                "send" => true,
                                "text" => "вам доступна акция"
                            ],
                            "activate_campaign" => [
                                "send" => true,
                                "text" => "вы участвуете в акции"
                            ],
                            "win_campaign" => [
                                "send" => true,
                                "text" => "Нвы доcтигли цели"
                            ],
                            "activate_win_campaign" => [
                                "send" => true,
                                "text" => "вы активировали вознаграждение"
                            ],
                            "end_campaign" => [
                                "send" => true,
                                "text" => "вы не достигли цели"
                            ],
                        ]),
                    ],
                    "count_points" => [
                        "type" => 'integer',
                        "comment" => 'Количество собранных баллов',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '2',
                    ],

                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/campaignpollanswers') {
            $model = 'CampaignPollAnswers';
            $table = "campaign_poll_answers";
            $titleMenu = 'Ответы пользователей';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "ответе пользователя",   // Измененяет информацию о
                    "many_r" => "ответов пользователей", // возвращает список
                    "one" => "ответ пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "campaign_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "question_id" => [
                        "type" => 'integer',
                        "comment" => 'Вопрос',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "answer_id" => [
                        "type" => 'integer',
                        "comment" => 'Ответ',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "val" => [
                        "type" => 'string',
                        "comment" => 'Ответ другое',
                        "nullable" => true,
                        'example' => 'text',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],


                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/campaignuser') {
            $model = 'CampaignUser';
            $table = "campaign_users";
            $titleMenu = 'Участники кампании';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "участнике рекламной кампании",   // Измененяет информацию о
                    "many_r" => "участников рекламной кампании", // возвращает список
                    "one" => "участника рекламной кампании",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "campaign_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "progress" => [
                        "type" => 'integer',
                        "comment" => 'Прогресс',
                        "default" => 0,
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "target" => [
                        "type" => 'integer',
                        "comment" => 'Цель',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Число выполненных кругов',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "is_activated" => [
                        "type" => 'integer',
                        "comment" => 'Активирована',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],

                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/coupon') {
            $model = 'Coupon';
            $table = "coupon";
            $titleMenu = 'Купоны';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "купоне",   // Измененяет информацию о
                    "many_r" => "купонов", // возвращает список
                    "one" => "купон",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'Короткое название',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "code" => [
                        "type" => 'string',
                        "comment" => 'Код купона',
                        "nullable" => true,
                        'example' => '123',
                    ],
                    "params" => [
                        "type" => 'json',
                        "comment" => 'Параметры купона',
                        "nullable" => true,
                        'example' => '[p1, p2]',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],

                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/couponuser') {
            $model = 'CouponUser';
            $table = "coupon_users";
            $titleMenu = 'Пользователи купона';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "пользователе купона", // Измененяет информацию о
                    "many_r" => "пользователей купона",// возвращает список
                    "one" => "пользователя купона",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "coupon_id" => [
                        "type" => 'integer',
                        "comment" => 'Купон',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],

                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/gift') {
            $model = 'Gift';
            $table = "gift";
            $titleMenu = 'Подарки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "подарке", // Измененяет информацию о
                    "many_r" => "подарков",// возвращает список
                    "one" => "подарок",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'Короткое название',
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'text',
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Название и описание на разных языках',
                        "nullable" => true,
                        'example' => '[name1, name2]',
                    ],
                    "image_id" => [
                        "type" => 'integer',
                        "comment" => 'Картинка',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "typegift_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип подарка',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "price_points" => [
                        "type" => 'integer',
                        "comment" => 'Цена в баллах',
                        "nullable" => true,
                        'example' => '20',
                    ],
                    "price_currency" => [
                        "type" => 'integer',
                        "comment" => 'Цена в валюте',
                        "nullable" => true,
                        'example' => '10',
                    ],
                    "good_id" => [
                        "type" => 'integer',
                        "comment" => 'Товар',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "external_id" => [
                        "type" => 'integer',
                        "comment" => 'Внешний ID',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "audience_id" => [
                        "type" => 'integer',
                        "comment" => 'Аудитория',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "region_id" => [
                        "type" => 'integer',
                        "comment" => 'Регион',
                        "nullable" => true,
                        'example' => '3',
                    ],
                    "level_id" => [
                        "type" => 'integer',
                        "comment" => 'Уровень лояльности',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "trigger_id" => [
                        "type" => 'integer',
                        "comment" => 'Триггер авто выдачи подарка',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "trigger_val" => [
                        "type" => 'string',
                        "comment" => 'Значение параметра триггера авто выдачи подарка',
                        "nullable" => true,
                        'example' => 'auto',
                    ],
                    "cnt_available" => [
                        "type" => 'integer',
                        "comment" => 'Общее количество',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "cnt_for_user" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во в 1 руки',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "dat_sale_from" => [
                        "type" => 'date',
                        "comment" => 'Срок действия акции от',
                        "nullable" => true,
                        'example' => '2023-01-01',
                    ],
                    "dat_sale_to" => [
                        "type" => 'date',
                        "comment" => 'Срок действия акции до',
                        "nullable" => true,
                        'example' => '2023-01-25',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-new, 1-activated, 2-blocked, 4-not available',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "name_internal" => [
                        "type" => 'string',
                        "comment" => 'Внутреннее название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "group_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа рекламных кампаний',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "link_gifts" => [
                        "type" => 'json',
                        "comment" => 'Связанные подарки',
                        "nullable" => true,
                        'example' => '[1,2,3]',
                    ],
                    "date_display_from" => [
                        "type" => 'date',
                        "comment" => 'Дата отображения от',
                        "nullable" => true,
                        'example' => '2023-11-01',
                    ],
                    "date_display_to" => [
                        "type" => 'date',
                        "comment" => 'Дата отображения до',
                        "nullable" => true,
                        'example' => '2023-11-08',
                    ],
                    "date_work_from" => [
                        "type" => 'date',
                        "comment" => 'Дата работы от',
                        "nullable" => true,
                        'example' => '2023-11-01',
                    ],
                    "date_work_to" => [
                        "type" => 'date',
                        "comment" => 'Дата работы до',
                        "nullable" => true,
                        'example' => '2023-11-08',
                    ],
                    "dat_activity" => [
                        "type" => 'date',
                        "comment" => 'Дата активности подарка',
                        "nullable" => true,
                        'example' => '2023-01-01',
                    ],
                    "date_activity_special" => [
                        "type" => 'integer',
                        "comment" => 'Дата активности рассчитвается: 1-ДР, 2- дата рег',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "display_days_before" => [
                        "type" => 'integer',
                        "comment" => 'Отображение за X дней до даты активнеости подарка',
                        "nullable" => true,
                        "default" => 5,
                        'example' => '2',
                    ],
                    "display_days_after" => [
                        "type" => 'integer',
                        "comment" => 'Отображение X дней после даты активнеости подарк',
                        "nullable" => true,
                        "default" => 5,
                        'example' => '2',
                    ],
                    "work_days_before" => [
                        "type" => 'integer',
                        "comment" => 'Период работы за X дней до даты активнеости подарка',
                        "nullable" => true,
                        "default" => 5,
                        'example' => '2',
                    ],
                    "work_days_after" => [
                        "type" => 'integer',
                        "comment" => 'Период работы X дней после даты активнеости подарка',
                        "nullable" => true,
                        "default" => 5,
                        'example' => '2',
                    ],
                    "geography" => [
                        "type" => 'json',
                        "comment" => 'География',
                        "nullable" => true,
                        'example' => '',
                    ],
                    "timetable" => [
                        "type" => 'json',
                        "comment" => 'Расписание',
                        "nullable" => true,
                        'example' => '2023-01-25',
                    ],
                    "is_active" => [
                        "type" => 'integer',
                        "comment" => 'Является активным',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "group_link_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа связанных подарков',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "push_settings" => [
                        "type" => 'json',
                        "comment" => 'Настройки уведомлений',
                        "nullable" => true,
                        'example' => '',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/groupcampaign') {
            $model = 'GroupCampaign';
            $table = "group_campaign";
            $titleMenu = 'Группы компаний';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "группе рекламных кампаний", // Измененяет информацию о
                    "many_r" => "групп рекламных кампаний",// возвращает список
                    "one" => "группу рекламных кампаний",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Подарки за молоко',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'moloko',
                    ],
                    "is_active" => [
                        "type" => 'integer',
                        "comment" => 'Активно',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/kindwin') {
            $model = 'KindWin';
            $table = "kind_win";
            $titleMenu = 'Виды вознаграждений';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "виде вознаграждений", // Измененяет информацию о
                    "many_r" => "видов вознаграждений",// возвращает список
                    "one" => "вид вознаграждения",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'Короткое название',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/likegood') {
            $model = 'LikeGood';
            $table = "like_goods";
            $titleMenu = 'Выбранные товары';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "выбранном товаре", // Измененяет информацию о
                    "many_r" => "выбранных товаров",// возвращает список
                    "one" => "выбранный товар",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "good_id" => [
                        "type" => 'integer',
                        "comment" => 'Товар',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "date_end_like" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата окончания выбора',
                        "nullable" => true,
                        'example' => '2023-01-01 00:00:00',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/mechanic') {
            $model = 'Mechanic';
            $table = "mechanic";
            $titleMenu = 'Механики';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                "resource" => 'campaign_mechanic',
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "механике", // Измененяет информацию о
                    "many_r" => "механик",// возвращает список
                    "one" => "механику",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'Короткое название',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "trigger_id" => [
                        "type" => 'integer',
                        "comment" => 'Триггер',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "listener" => [
                        "type" => 'string',
                        "comment" => 'Листенер класс',
                        "nullable" => true,
                        'example' => 'class2',
                    ],
                    "src" => [
                        "type" => 'jsonb',
                        "comment" => 'Правила механики',
                        "nullable" => true,
                        "default" => '{
            "required": [
                "points",
                "type_points_included",
                "type_points_excluded",
                "type_buys",
                "goods_included",
                "goods_excluded",
                "category_goods_included",
                "category_goods_excluded",
                "min_points"
            ]
        }',
                        'example' => '[type_buys, type_points_included]',
                    ],

                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/promocode') {
            $model = 'Promocode';
            $table = "promocode";
            $titleMenu = 'Промокоды';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "промокоде", // Измененяет информацию о
                    "many_r" => "промокодов",// возвращает список
                    "one" => "промокод",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'короткое название',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "code" => [
                        "type" => 'string',
                        "comment" => 'Код',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'text',
                    ],
                    "group_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа рекламных кампаний',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "date_start" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата начала работы',
                        "nullable" => true,
                        'example' => '2023-01-01 00:00:00',
                    ],
                    "date_end" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата окончания работы',
                        "nullable" => true,
                        'example' => '2023-01-24 00:00:00',
                    ],
                    "timetable_id" => [
                        "type" => 'integer',
                        "comment" => 'Расписание',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "kind_win_id" => [
                        "type" => 'integer',
                        "comment" => 'Вид вознаграждения',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "type_promocode_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип промокода',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '2',
                    ],
                    "audience_id" => [
                        "type" => 'integer',
                        "comment" => 'Аудитория',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "is_audience_new_only" => [
                        "type" => 'integer',
                        "comment" => 'Только новые пользователи',
                        "nullable" => true,
                        "default" => 2,
                        'example' => '2',
                    ],
                    "params_win" => [
                        "type" => 'jsonb',
                        "comment" => 'Параметры вознаграждения',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "geography" => [
                        "type" => 'jsonb',
                        "comment" => 'География',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "imgs" => [
                        "type" => 'jsonb',
                        "comment" => 'Картинки',
                        "nullable" => true,
                        'example' => '[1,2]',
                    ],
                    "is_available" => [
                        "type" => 'integer',
                        "comment" => 'Доступный',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                    "is_active" => [
                        "type" => 'integer',
                        "comment" => 'Активный',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                    "is_multi" => [
                        "type" => 'integer',
                        "comment" => 'Мульти',
                        "nullable" => true,
                        "default" => 2,
                        'example' => '1',
                    ],
                    "multi_cnt_available" => [
                        "type" => 'integer',
                        "comment" => 'Доступное кол-во',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/promocodeuser') {
            $model = 'PromocodeUser';
            $table = "promocode_users";
            $titleMenu = 'Пользователи промокодов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "пользователе промокода", // Измененяет информацию о
                    "many_r" => "пользователей промокодов",// возвращает список
                    "one" => "пользователя промокода",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Название',
                    ],
                    "promocode_id" => [
                        "type" => 'integer',
                        "comment" => 'Промокод',
                        "nullable" => true,
                        'example' => '2',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        'example' => '3',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Число активаций',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '3',
                    ],
                    "is_activated" => [
                        "type" => 'integer',
                        "comment" => 'Активирован',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/timetable') {
            $model = 'Timetable';
            $table = "timetable";
            $titleMenu = 'Расписания';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                "resource" => 'marketing_timetable',
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "расписании", // Измененяет информацию о
                    "many_r" => "расписаний",// возвращает список
                    "one" => "расписание",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        'example' => '3',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Служебное',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Расписание',
                        "nullable" => true,
                        "default" => '{"mon":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":false},"tue":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":false},"wed":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":false},"thur":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":false},"fri":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":false},"sat":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":true},"sun":{"start":"10:00","end":"19:00","start_dinner":"13:00","end_dinner":"14:00","off":true}}',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/typeactivation') {
            $model = 'TypeActivation';
            $table = "type_activation";
            $titleMenu = 'Типы активации';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе активации", // Измененяет информацию о
                    "many_r" => "типов активации",// возвращает список
                    "one" => "тип активации",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/typegift') {
            $model = 'TypeGift';
            $table = "type_gift";
            $titleMenu = 'Типы подарков';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе подарка", // Измененяет информацию о
                    "many_r" => "типов подарков",// возвращает список
                    "one" => "тип подарка",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "default" => 1,
                        'example' => 'name1',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/typepromocode') {
            $model = 'TypePromocode';
            $table = "type_promocode";
            $titleMenu = 'Типы промокодов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе промокода", // Измененяет информацию о
                    "many_r" => "типов промокодов",// возвращает список
                    "one" => "тип промокода",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/typewin') {
            $model = 'TypeWin';
            $table = "type_win";
            $titleMenu = 'Типы вознаграждений';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе вознаграждения", // Измененяет информацию о
                    "many_r" => "типов вознаграждений",// возвращает список
                    "one" => "тип вознаграждения",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'name',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/marketing/usergift') {
            $model = 'UserGift';
            $table = "user_gift";
            $titleMenu = 'Пользователи подарков';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "пользователе подарка", // Измененяет информацию о
                    "many_r" => "пользователей подарков",// возвращает список
                    "one" => "пользователя подарка",// Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'name1',
                    ],
                    "company_id" => [
                        "type" => 'integer',
                        "comment" => 'Компания',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "default" => 1,
                        'example' => '1',
                    ],
                    "gift_id" => [
                        "type" => 'integer',
                        "comment" => 'Подарок',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "ext_gift_id" => [
                        "type" => 'integer',
                        "comment" => 'Внешний подарок',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во выданых',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус: 0-доступно, 1-активирован, 2-отключен 4-неиспользован',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function work()
    {
        //----------------------------------
        if ($this->string == '/work/orders') {
            $model = 'Order';
            $table = "order";
            $titleMenu = 'Заказы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "заказе",   // Измененяет информацию о
                    "many_r" => "заказов", // возвращает список
                    "one" => "заказ",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Заказ 5',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                        'example' => 'order_5',
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'Клиент',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "place_id" => [
                        "type" => 'integer',
                        "comment" => 'Место работы',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "tariff_id" => [
                        "type" => 'integer',
                        "comment" => 'Тариф',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "date_id" => [
                        "type" => 'integer',
                        "comment" => 'Дата',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "req_cnt_executors" => [
                        "type" => 'integer',
                        "comment" => 'Количество исполнителей требуемое',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "cnt_executors" => [
                        "type" => 'integer',
                        "comment" => 'Количество исполнителей подобранное',
                        "nullable" => true,
                        'example' => '5',
                    ],
                    "req_med" => [
                        "type" => 'integer',
                        "comment" => 'Требуется медкнижка',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "payStatus" => [
                        "type" => 'integer',
                        "comment" => 'Статус оплат',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "ext_id" => [
                        "type" => 'integer',
                        "comment" => 'Статус оплат',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "changed_at" => [
                        "type" => 'timestamp',
                        "comment" => 'Изменены исполнители в заказе',
                        "nullable" => true,
                        'example' => '2023-05-04 11:45:12',
                    ],
                    "sync_at" => [
                        "type" => 'timestamp',
                        "comment" => 'Синхронизация',
                        "nullable" => true,
                        'example' => '2023-05-04 11:45:12',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function reports()
    {
        //----------------------------------
        if ($this->string == '/reports/templates') {
            $model = 'Template';
            $table = "template";
            $titleMenu = 'Шаблоны';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "шаблоне",   // Измененяет информацию о
                    "many_r" => "шаблонов", // возвращает список
                    "one" => "шаблон",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Отчет по исполнителям',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string = '/reports/reports') {
            $model = 'Report';
            $table = "reports";
            $titleMenu = 'Отчеты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "отчете",   // Измененяет информацию о
                    "many_r" => "отчетов", // возвращает список
                    "one" => "отчет",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Отчет по исполнителям',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "template_id" => [
                        "type" => 'integer',
                        "comment" => 'Шаблон',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "params" => [
                        "type" => 'json',
                        "comment" => 'Параметры',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/reports/sverka') {
            $model = 'SverkaMonth';
            $table = "sverka_month";
            $titleMenu = 'Сверка за месяц';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сверке за месяц",   // Измененяет информацию о
                    "many_r" => "сверок за месяц", // возвращает список
                    "one" => "сверку за месяц",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'Клиент',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "firmclient_id" => [
                        "type" => 'integer',
                        "comment" => 'Юрлицо клиента',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "date_sverka" => [
                        "type" => 'date',
                        "comment" => 'Дата сверки',
                        "nullable" => true,
                        "example" => "2023-05-30"
                    ],
                    "date_id" => [
                        "type" => 'integer',
                        "comment" => 'Дата',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "profession_id" => [
                        "type" => 'string',
                        "comment" => 'Профессия',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "cnt_people" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во человек',
                        "nullable" => true,
                        "example" => "12"
                    ],
                    "hours" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во часов',
                        "nullable" => true,
                        "example" => "12"
                    ],
                    "tariff_id" => [
                        "type" => 'integer',
                        "comment" => 'Тариф',
                        "nullable" => true,
                    ],
                    "sum" => [
                        "type" => 'float',
                        "comment" => 'Сумма',
                        "nullable" => true,
                        "example" => "12000"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------

    }

    public function fakes()
    {
        if ($this->string == '/fakes/user') {
            $model = 'User';
            $table = "users";
            $titleMenu = 'Пользователи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "пользователе",   // Измененяет информацию о
                    "many_r" => "пользователей", // возвращает список
                    "one" => "пользователя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "nick" => [
                        "type" => 'string',
                        "comment" => 'Ник',
                        "nullable" => true,
                        'example' => 'ник',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Имя',
                        "nullable" => true,
                        'example' => 'Иван',
                    ],
                    "middle_name" => [
                        "type" => 'string',
                        "comment" => 'Отчество',
                        "nullable" => true,
                        'example' => 'Иванович',
                    ],
                    "last_name" => [
                        "type" => 'string',
                        "comment" => 'Фамилия',
                        "nullable" => true,
                        'example' => 'Иванов',
                    ],
                    "birthday" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата рождения',
                        "nullable" => true,
                        'example' => '1990-01-01',
                    ],
                    "sex" => [
                        "type" => 'string',
                        "comment" => 'Пол: M или F',
                        "nullable" => true,
                        "default" => 'M',
                        'example' => 'M',
                    ],
                    "email" => [
                        "type" => 'string',
                        "comment" => 'Емайл',
                        "nullable" => true,
                        "unique" => true,
                        'example' => 'ivan@mail.ru',
                    ],
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                        "unique" => true,
                        'example' => '+170 341 235 555',
                    ],
                    "password" => [
                        "type" => 'string',
                        "comment" => 'Пароль',
                        "nullable" => true,
                        'example' => 'pass_123',
                    ],
                    "owner" => [
                        "type" => 'integer',
                        "comment" => 'Владелец',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "last_use_at" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата последнего использования',
                        "nullable" => true,
                        'example' => '2023-01-05 12:00:00',
                    ],
                    "lang" => [
                        "type" => 'string',
                        "comment" => 'Язык',
                        "nullable" => true,
                        "default" => 'ru',
                        'example' => 'en',
                    ],
                    "age" => [
                        "type" => 'integer',
                        "comment" => 'Возраст',
                        "nullable" => true,
                        'example' => '22',
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Количество использований',
                        "nullable" => true,
                        "onDelete" => 'cascade',
                        'example' => '3',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "0"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/bots') {
            $model = 'Bot';
            $table = "bots";
            $titleMenu = 'Боты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "боте",   // Измененяет информацию о
                    "many_r" => "ботов", // возвращает список
                    "one" => "бот",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "lastrun" => [
                        "type" => 'timestamp',
                        "comment" => 'Время последнего запуска',
                        "nullable" => true,
                        "example" => "2012-01-04 06:45:23"
                    ],
                    "options" => [
                        "type" => 'json',
                        "comment" => 'Настройки',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги - типы сервисов',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "version" => [
                        "type" => 'integer',
                        "comment" => 'Версия',
                        "default" => 0,
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
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/bot_actions') {
            $model = 'BotAction';
            $table = "bot_actions";
            $titleMenu = 'Действия ботов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "действии",   // Измененяет информацию о
                    "many_r" => "действий", // возвращает список
                    "one" => "действие",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "descr" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "bot_id" => [
                        "type" => 'integer',
                        "comment" => 'Бот',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "lastrun" => [
                        "type" => 'timestamp',
                        "comment" => 'Время последнего запуска',
                        "nullable" => true,
                        "example" => "2012-01-04 06:45:23"
                    ],
                    "options" => [
                        "type" => 'json',
                        "comment" => 'Настройки',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/bot_jobs') {
            $model = 'BotJob';
            $table = "bot_jobs";
            $titleMenu = 'Задания для ботов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "задании",   // Измененяет информацию о
                    "many_r" => "заданий", // возвращает список
                    "one" => "задание",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "bot_id" => [
                        "type" => 'string',
                        "comment" => 'Бот',
                        "nullable" => true,
                    ],
                    "action_id" => [
                        "type" => 'string',
                        "comment" => 'Действие бота',
                        "nullable" => true,
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Параметры задачи',
                        "nullable" => true,
                    ],
                    "result" => [
                        "type" => 'json',
                        "comment" => 'Результаты',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/services') {
            $model = 'Service';
            $table = "service";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервисе",   // Измененяет информацию о
                    "many_r" => "сервисов", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги - типы сервисов',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/accounts') {
            $model = 'Account';
            $table = "accounts";
            $titleMenu = 'Акаунты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "аккаунте",   // Измененяет информацию о
                    "many_r" => "аккаунтов", // возвращает список
                    "one" => "аккаунт",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Отчет по исполнителям',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                    ],
                    "fake_user_id" => [
                        "type" => 'integer',
                        "comment" => 'Персонаж',
                        "nullable" => true,
                    ],
                    "host" => [
                        "type" => 'string',
                        "comment" => 'Хост',
                        "nullable" => true,
                        "example" => "www.mail.ru"
                    ],
                    "login" => [
                        "type" => 'string',
                        "comment" => 'Логин',
                        "nullable" => true,
                        "example" => "joker"
                    ],
                    "password" => [
                        "type" => 'string',
                        "comment" => 'Пароль',
                        "nullable" => true,
                        "example" => "c523de3"
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Все данные',
                        "nullable" => true,
                    ],
                    "token" => [
                        "type" => 'string',
                        "comment" => 'Токен',
                        "nullable" => true,
                        "example" => "x34dfgg534gghghggu7445"
                    ],
                    "options" => [
                        "type" => 'json',
                        "comment" => 'Настройки',
                        "nullable" => true,
                    ],
                    "comment" => [
                        "type" => 'string',
                        "comment" => 'Комментарий',
                        "nullable" => true,
                        "example" => "для накрутки Кинопоиска"
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "dat_last_run" => [
                        "type" => 'timestamp',
                        "comment" => 'Время последнего использования',
                        "nullable" => true,
                        "example" => "2018-01--06 12:56:23"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/proxies') {
            $model = 'Proxy';
            $table = "proxies";
            $titleMenu = 'Прокси';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "аккаунте",   // Измененяет информацию о
                    "many_r" => "аккаунтов", // возвращает список
                    "one" => "аккаунт",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Отчет по исполнителям',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                    ],
                    "type_proxy_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип прокси',
                        "nullable" => true,
                        "default" => 1
                    ],
                    "kind_proxy_id" => [
                        "type" => 'integer',
                        "comment" => 'Вид прокси',
                        "nullable" => true,
                        "default" => 1
                    ],
                    "host" => [
                        "type" => 'string',
                        "comment" => 'Хост',
                        "nullable" => true,
                        "example" => "www.mail.ru"
                    ],
                    "port" => [
                        "type" => 'integer',
                        "comment" => 'Порт',
                        "nullable" => true,
                        "example" => "12345"
                    ],
                    "login" => [
                        "type" => 'string',
                        "comment" => 'Логин',
                        "nullable" => true,
                        "example" => "joker"
                    ],
                    "password" => [
                        "type" => 'string',
                        "comment" => 'Пароль',
                        "nullable" => true,
                        "example" => "c523de3"
                    ],
                    "is_work_by_ip" => [
                        "type" => 'integer',
                        "comment" => 'Работает по белому списку IP',
                        "nullable" => true,
                        "example" => "2",
                        "default" => 2
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Все данные',
                        "nullable" => true,
                    ],
                    "comment" => [
                        "type" => 'string',
                        "comment" => 'Комментарий',
                        "nullable" => true,
                        "example" => "прокси от партнеров"
                    ],
                    "cnt" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во использований',
                        "nullable" => true,
                        "example" => "15"
                    ],
                    "dat_last_run" => [
                        "type" => 'timestamp',
                        "comment" => 'Время последнего использования',
                        "nullable" => true,
                        "example" => "2018-01-06 12:56:23"
                    ],
                    "tags" => [
                        "type" => 'json',
                        "comment" => 'Тэги',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/sessions') {
            $model = 'Session';
            $table = "sessions";
            $titleMenu = 'Сессии';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сессии",   // Измененяет информацию о
                    "many_r" => "сессий", // возвращает список
                    "one" => "сессию",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "bot_id" => [
                        "type" => 'integer',
                        "comment" => 'Бот',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "data" => [
                        "type" => 'json',
                        "comment" => 'Входные данные',
                        "nullable" => true,
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Данные от бота',
                        "nullable" => true,
                    ],
                    "result" => [
                        "type" => 'json',
                        "comment" => 'Результат',
                        "nullable" => true,
                    ],
                    "update" => [
                        "type" => 'json',
                        "comment" => 'Обновление аккаунтов',
                        "nullable" => true,
                    ],
                    "need" => [
                        "type" => 'json',
                        "comment" => 'Необходимые параметры',
                        "nullable" => true,
                    ],
                    "dat_last_run" => [
                        "type" => 'timestamp',
                        "comment" => 'Время последнего использования',
                        "nullable" => true,
                        "example" => "2018-01--06 12:56:23"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/accountLog') {
            $model = 'AccountLog';
            $table = "account_logs";
            $titleMenu = 'Логи аккаунтов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "логе аккаунта",   // Измененяет информацию о
                    "many_r" => "логов аккаунтов", // возвращает список
                    "one" => "лог аккаунта",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "account_id" => [
                        "type" => 'integer',
                        "comment" => 'Аккаунт',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/scenarios') {
            $model = 'Scenario';
            $table = "scenarios";
            $titleMenu = 'Сценарии';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сценарии",   // Измененяет информацию о
                    "many_r" => "сценариев", // возвращает список
                    "one" => "сценарий",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "src" => [
                        "type" => 'text',
                        "comment" => 'Исходник',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/card_shems') {
            $model = 'CardShem';
            $table = "card_shems";
            $titleMenu = 'Типы карточек';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "типе карточки",   // Измененяет информацию о
                    "many_r" => "типов карточек", // возвращает список
                    "one" => "тип карточки",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Исходник',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/cards') {
            $model = 'Card';
            $table = "cards";
            $titleMenu = 'Карточки';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "карточке",   // Измененяет информацию о
                    "many_r" => "карточке", // возвращает список
                    "one" => "карточку",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "type_card_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип карточки',
                        "nullable" => true,
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Исходник',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/runs') {
            $model = 'Run';
            $table = "runs";
            $titleMenu = 'Запуски';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "запуске",   // Измененяет информацию о
                    "many_r" => "запусков", // возвращает список
                    "one" => "запуск",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "scenario_id" => [
                        "type" => 'integer',
                        "comment" => 'Сценарий',
                        "nullable" => true,
                    ],
                    "progress" => [
                        "type" => 'integer',
                        "comment" => 'Прогресс',
                        "nullable" => true,
                    ],
                    "max_steps" => [
                        "type" => 'integer',
                        "comment" => 'Кол-во шагов',
                        "nullable" => true,
                    ],
                    "status_last_step" => [
                        "type" => 'integer',
                        "comment" => 'Статус последнего шага',
                        "default" => 0,
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
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/scenario_logs') {
            $model = 'ScenarioLog';
            $table = "scenario_logs";
            $titleMenu = 'Логи сценариев';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "логе",   // Измененяет информацию о
                    "many_r" => "логов", // возвращает список
                    "one" => "лог",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "scenario_id" => [
                        "type" => 'integer',
                        "comment" => 'Сценарий',
                        "nullable" => true,
                    ],
                    "run_id" => [
                        "type" => 'integer',
                        "comment" => 'Запуск',
                        "nullable" => true,
                    ],
                    "bot_id" => [
                        "type" => 'integer',
                        "comment" => 'Бот',
                        "nullable" => true,
                    ],
                    "action" => [
                        "type" => 'string',
                        "comment" => 'Действие',
                        "nullable" => true,
                    ],
                    "session" => [
                        "type" => 'integer',
                        "comment" => 'Сессия',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/logs') {
            $model = 'Log';
            $table = "fakes.logs";
            $titleMenu = 'Логи ботов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "логе",   // Измененяет информацию о
                    "many_r" => "логов", // возвращает список
                    "one" => "лог",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "bot_id" => [
                        "type" => 'integer',
                        "comment" => 'Бот',
                        "nullable" => true,
                    ],
                    "action" => [
                        "type" => 'string',
                        "comment" => 'Действие',
                        "nullable" => true,
                    ],
                    "session_id" => [
                        "type" => 'integer',
                        "comment" => 'Сессия',
                        "nullable" => true,
                    ],
                    "data" => [
                        "type" => 'longText',
                        "comment" => 'Данные',
                        "nullable" => true,
                    ],
                    "file_id" => [
                        "type" => 'integer',
                        "comment" => 'Файл',
                        "nullable" => true,
                    ],
                    "file_path" => [
                        "type" => 'string',
                        "comment" => 'Путь до файла',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/sms') {
            $model = 'Sms';
            $table = "sms";
            $titleMenu = 'SMS коды';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "sms",   // Измененяет информацию о
                    "many_r" => "sms", // возвращает список
                    "one" => "sms",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое сообщение',
                        "nullable" => true,
                    ],
                    "date_message" => [
                        "type" => 'timestamp',
                        "comment" => 'Время сообщение',
                        "nullable" => true,
                    ],
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Телефон',
                        "nullable" => true,
                    ],
                    "from" => [
                        "type" => 'string',
                        "comment" => 'От кого',
                        "nullable" => true,
                    ],
                    "text" => [
                        "type" => 'string',
                        "comment" => 'Сообщение',
                        "nullable" => true,
                    ],
                    "mess_id" => [
                        "type" => 'integer',
                        "comment" => 'ID сообщения',
                        "nullable" => true,
                    ],
                    "data" => [
                        "type" => 'longText',
                        "comment" => 'Данные',
                        "nullable" => true,
                    ],
                    "code" => [
                        "type" => 'string',
                        "comment" => 'код',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/fakes/sms_rules') {
            $model = 'SmsRule';
            $table = "sms_rules";
            $titleMenu = 'SMS правила';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "sms правиле",   // Измененяет информацию о
                    "many_r" => "sms правил", // возвращает список
                    "one" => "sms правило",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
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
                    "regex" => [
                        "type" => 'text',
                        "comment" => 'Регулярка',
                        "nullable" => true,
                    ],
                    "matches" => [
                        "type" => 'string',
                        "comment" => 'Совпадения',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function money()
    {
        if ($this->string == '/money/executors') {
            $model = 'Executor';
            $table = "executors";
            $titleMenu = 'Исполнитель';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "исполнителе",   // Измененяет информацию о
                    "many_r" => "исполнителей", // возвращает список
                    "one" => "исполнителя",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование исполнителя',
                        "nullable" => true,
                        'example' => 'Создать задачу',
                    ],
                    "shortname" => [
                        "type" => 'integer',
                        "comment" => 'Краткое наименование исполнителя',
                        "nullable" => true,
                        'example' => 'Задача',
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'ID для связи',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ]
                ],
                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/money/calcmethods') {
            $model = 'CalcMethod';
            $table = "calcmethods";
            $titleMenu = 'Методы расчетов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "методе расчетов",   // Измененяет информацию о
                    "many_r" => "методов расчетов", // возвращает список
                    "one" => "метод расчета",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'text',
                        "comment" => 'Конечный JSON',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "shortname" => [
                        "type" => 'text',
                        "comment" => 'Только измененные поля',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '0',
                    ],
                    "desc" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/money/valute') {
            $model = 'Valute';
            $table = "valutes";
            $titleMenu = 'Валюты';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "валюте",   // Измененяет информацию о
                    "many_r" => "валют", // возвращает список
                    "one" => "валюту",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'text',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "shortname" => [
                        "type" => 'text',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '0',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/money/projectmembers') {
            $model = 'ProjectMember';
            $table = "project_members";
            $titleMenu = 'Участники проектов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "проекте",   // Измененяет информацию о
                    "many_r" => "проектов", // возвращает список
                    "one" => "проект",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "project_id" => [
                        "type" => 'integer',
                        "comment" => 'ID проекта',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "client_id" => [
                        "type" => 'integer',
                        "comment" => 'ID клиента',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "executor_id" => [
                        "type" => 'integer',
                        "comment" => 'ID исполнителя',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/money/balances') {
            $model = 'Balance';
            $table = "balances";
            $titleMenu = 'Баланс';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "балансе",   // Измененяет информацию о
                    "many_r" => "балансов", // возвращает список
                    "one" => "баланс",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'text',
                        "comment" => 'Наименование',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'text',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                    ],
                    "money_at" => [
                        "type" => 'date',
                        "comment" => 'Дата',
                        "nullable" => true,
                        'example' => '2024-01-01',
                    ],
                    "schet_id" => [
                        "type" => 'integer',
                        "comment" => 'Счет',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "balance_start" => [
                        "type" => 'float',
                        "comment" => 'Баланс на начало дня',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "balance_end" => [
                        "type" => 'float',
                        "comment" => 'Баланс на конец дня',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "balance_delta" => [
                        "type" => 'float',
                        "comment" => 'Изменение баланса',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '0',
                    ],
                    "desc" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/money/balance_logs') {
            $model = 'BalanceLog';
            $table = "balance_logs";
            $titleMenu = 'Балансы (факт)';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "логе баланса счетов",   // Измененяет информацию о
                    "many_r" => "логов балансов счетов", // возвращает список
                    "one" => "лог баланса счетов",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "schet_id" => [
                        "type" => 'integer',
                        "comment" => 'Счет',
                        "nullable" => true,
                    ],
                    "balance" => [
                        "type" => 'float',
                        "comment" => 'Баланс',
                        "nullable" => true,
                    ],
                    "checked_at" => [
                        "type" => 'datetime',
                        "comment" => 'Дата и время',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/money/operations_groups') {
            $model = 'OperationGroup';
            $table = "operations_groups";
            $titleMenu = 'Группы операций';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "группе операций",   // Измененяет информацию о
                    "many_r" => "групп операций", // возвращает список
                    "one" => "группу",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                    ],
                    "desc" => [
                        "type" => 'string',
                        "comment" => 'Описание',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function pay()
    {
        //----------------------------------
        if ($this->string == '/pay/payments') {
            $model = 'Payment';
            $table = "payments";
            $titleMenu = 'Платежи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "платеже",   // Измененяет информацию о
                    "many_r" => "платежей", // возвращает список
                    "one" => "платеж",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование задачи',
                        "nullable" => true,
                    ],
                    "task_id" => [
                        "type" => 'integer',
                        "comment" => 'ourcrm id задачи ',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "worker_id" => [
                        "type" => 'integer',
                        "comment" => 'Сотрудник',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "date_change" => [
                        "type" => 'timestamp',
                        "comment" => 'Дата изменения',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "sum" => [
                        "type" => 'integer',
                        "comment" => 'Сумма',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "pay_status" => [
                        "type" => 'string',
                        "comment" => 'Статус оплаты',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "pay_date" => [
                        "type" => 'date',
                        "comment" => 'Дата оплаты',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'JSON ответ от плат. системы',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "url" => [
                        "type" => 'string',
                        "comment" => 'URL задачи',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "iteration" => [
                        "type" => 'integer',
                        "comment" => 'Итерация',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_nalog" => [
                        "type" => 'integer',
                        "comment" => 'Оплата налога',
                        "nullable" => true,
                    ],
                    "is_iter" => [
                        "type" => 'integer',
                        "comment" => 'Оплата итерации',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "payroll_id" => [
                        "type" => 'string',
                        "comment" => 'ID реестра оплаты',
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
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/pay/services') {
            $model = 'Service';
            $table = "service";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервисе",   // Измененяет информацию о
                    "many_r" => "сервисов", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                        'example' => 'Namecheap',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "options" => [
                        "type" => 'json',
                        "comment" => 'Параметры',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/pay/balances') {
            $model = 'Balance';
            $table = "balances";
            $titleMenu = 'Балансы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервисе",   // Измененяет информацию о
                    "many_r" => "сервисов", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Название',
                        "nullable" => true,
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое название',
                        "nullable" => true,
                    ],
                    "service_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервис',
                        "nullable" => true,
                    ],
                    "balance" => [
                        "type" => 'float',
                        "comment" => 'Баланс',
                        "nullable" => true,
                    ],
                    "checked_at" => [
                        "type" => 'datetime',
                        "comment" => 'Дата и время',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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

    public function tasks()
    {
        if ($this->string == '/tasks/tasks') {
            $model = 'Task';
            $table = "tasks";
            $titleMenu = 'Задачи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "задаче",   // Измененяет информацию о
                    "many_r" => "задач", // возвращает список
                    "one" => "задачу",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "source_id" => [
                        "type" => 'integer',
                        "comment" => 'Id источника',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "ext_task_nom" => [
                        "type" => 'string',
                        "comment" => 'Номер задачи из источника',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование задачи',
                        "nullable" => true,
                        'example' => 'Создать задачу',
                    ],
                    "shortname" => [
                        "type" => 'integer',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        'example' => 'Задача',
                    ],
                    "employee_id" => [
                        "type" => 'integer',
                        "comment" => 'Id пользователя Moo.Team',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание задачи',
                        "nullable" => true,
                        'example' => 'Составить подробное описание задачи',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ],
                    "status_ext" => [
                        "type" => 'integer',
                        "comment" => 'Статус Moo.Team',
                        "nullable" => true,
                        'example' => '0',
                    ],
                    "iteration" => [
                        "type" => 'integer',
                        "comment" => 'Количество изменений на статус Тестировние',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ],
                    "payment" => [
                        "type" => 'integer',
                        "comment" => 'Оплата',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ],
                    "plan_time" => [
                        "type" => 'string',
                        "comment" => 'Запланированное время',
                        "nullable" => true,
                        'example' => '01:00',
                    ],
                    "fact_time" => [
                        "type" => 'string',
                        "comment" => 'Фактическое время',
                        "nullable" => true,
                        'example' => '01:00',
                    ],
                    "src" => [
                        "type" => 'text',
                        "comment" => 'JSON',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                ],


                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/tasks/logs') {
            $model = 'Log';
            $table = "logs";
            $titleMenu = 'Логи';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "логах",   // Измененяет информацию о
                    "many_r" => "логов", // возвращает список
                    "one" => "лог",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "taskId" => [
                        "type" => 'string',
                        "comment" => 'moo.team_taskId',
                        "nullable" => true,
                        'example' => '465030',
                    ],
                    "src_from" => [
                        "type" => 'text',
                        "comment" => 'Исходный JSON',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "src_to" => [
                        "type" => 'text',
                        "comment" => 'Конечный JSON',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "src" => [
                        "type" => 'text',
                        "comment" => 'Только измененные поля',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                    "status" => [
                        "type" => 'text',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '0',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/tasks/sources') {
            $model = 'Source';
            $table = "sources";
            $titleMenu = 'Источники';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "источниках",   // Измененяет информацию о
                    "many_r" => "источников", // возвращает список
                    "one" => "источник",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование источника',
                        "nullable" => true,
                        'example' => 'Яндекс.Трекер',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Короткое наименование источника',
                        "nullable" => true,
                        'example' => 'yatracker',
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        'example' => '1',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/tasks/workers') {
            $model = 'Worker';
            $table = "workers";
            $titleMenu = 'Сотрудники';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сотрудниках",   // Измененяет информацию о
                    "many_r" => "сотрудников", // возвращает список
                    "one" => "сотрудника",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        "example" => "Иван"
                    ],
                    "user_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "ext_id" => [
                        "type" => 'integer',
                        "comment" => 'Пользователь в сервисе',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "type_of_id" => [
                        "type" => 'integer',
                        "comment" => 'Тип оформления',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "phone" => [
                        "type" => 'string',
                        "comment" => 'Номер телефона',
                        "nullable" => true,
                        "example" => "Задача"
                    ],
                    "inn" => [
                        "type" => 'string',
                        "comment" => 'ИНН',
                        "nullable" => true,
                        "example" => "3525252352"
                    ],
                    "is_reg" => [
                        "type" => 'text',
                        "comment" => 'Модерация QUGO',
                        "default" => 2,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "bet" => [
                        "type" => 'integer',
                        "comment" => 'Ставка руб\час',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 1,
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "is_koef_nalog" => [
                        "type" => 'integer',
                        "comment" => 'Учет налога',
                        "nullable" => true,
                        "default" => 2,
                        "example" => "1"
                    ],
                    "is_koef_iter" => [
                        "type" => 'integer',
                        "comment" => 'Учет 1-ой итерации',
                        "nullable" => true,
                        "default" => 2,
                        "example" => "2"
                    ],
                    "koef_iter_val" => [
                        "type" => 'string',
                        "comment" => 'Налоговый коэф.',
                        "nullable" => true,
                        "default" => "1.1",
                        "example" => "1.1"
                    ],
                    "koef_nalog_val" => [
                        "type" => 'string',
                        "comment" => 'Коэф. за итерацию',
                        "nullable" => true,
                        "default" => "1.06",
                        "example" => "1.06"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/tasks/extstatuses') {
            $model = 'ExtStatus';
            $table = "ext_statuses";
            $titleMenu = 'Внешние статусы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "внешних статусах",   // Измененяет информацию о
                    "many_r" => "внешних статусов", // возвращает список
                    "one" => "внешний статус",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование статуса',
                        "nullable" => true,
                        "example" => "Создать задачу"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Внешний статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "source_id" => [
                        "type" => 'integer',
                        "comment" => 'Источник статуса',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "payment_status" => [
                        "type" => 'integer',
                        "comment" => 'Платежный статус',
                        "nullable" => true,
                        "default" => null,
                        "unique" => '',
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/tasks/logstatuses') {
            $model = 'LogStatus';
            $table = "log_statuses";
            $titleMenu = 'Статусы логов';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "статусах лога",   // Измененяет информацию о
                    "many_r" => "статусов лога", // возвращает список
                    "one" => "статус лога",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование статуса',
                        "nullable" => true,
                        "example" => "Создать задачу"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Внешний статус',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Платежный статус',
                        "nullable" => true,
                        "default" => 1,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                ]
            ];
        }
        //----------------------------------

    }

    public function cabinet()
    {
        if ($this->string == '/cabinet/tafiff') {
            $model = 'Tariff';
            $table = "tariffs";
            $titleMenu = 'Тарифы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "тарифе",   // Измененяет информацию о
                    "many_r" => "тарифов", // возвращает список
                    "one" => "тариф",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        'example' => 'Первый',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        'example' => 'one',
                    ],
                    "period" => [
                        "type" => 'integer',
                        "comment" => 'Период',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "price" => [
                        "type" => 'float',
                        "comment" => 'Цена',
                        "nullable" => true,
                        'example' => '1000',
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ],
                    "src" => [
                        "type" => 'text',
                        "comment" => 'JSON',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
        if ($this->string == '/cabinet/finance') {
            $model = 'Finance';
            $table = "finances";
            $titleMenu = 'Финансы';
            $modelLower = strtolower($model);
            $this->moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "финансах",   // Измененяет информацию о
                    "many_r" => "финансов", // возвращает список
                    "one" => "расход или доход",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        'example' => 'Первый',
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        'example' => 'one',
                    ],
                    "direction" => [
                        "type" => 'integer',
                        "comment" => 'Операция',
                        "nullable" => true,
                        'example' => '1',
                    ],
                    "sum" => [
                        "type" => 'float',
                        "comment" => 'Сумма',
                        "nullable" => true,
                        'example' => '1000',
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание',
                        "nullable" => true,
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "nullable" => true,
                        "default" => 0,
                        'example' => '0',
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'JSON',
                        "nullable" => true,
                        'example' => 'json',
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$this->moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$this->moduleLower/$modelLower',
                    'model' => $model::class,
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
        //----------------------------------
    }

    public function access()
    {
        if ($this->string == '/access/groups') {
            $model = 'Group';
            $table = "groups";
            $titleMenu = 'Группы';
            $modelLower = strtolower($model);
            $moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "группе",   // Измененяет информацию о
                    "many_r" => "группы", // возвращает список
                    "one" => "группу",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$this->module\\Models\\$model',
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
                ]
            ];
            $this->string = '/access/services';

        }
        if ($this->module == 'RentAuto') {
            //----------------------------------
            if ($this->string == '/rentauto/auto') {
                $model = 'Auto';
                $table = "auto";
                $titleMenu = 'Автомобили';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "шаблоне",   // Измененяет информацию о
                        "many_r" => "шаблонов", // возвращает список
                        "one" => "шаблон",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Volvo XC60',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "cnt" => [
                            "type" => 'integer',
                            "comment" => 'Кол-во использований',
                            "nullable" => true,
                            "example" => "15"
                        ],
                        "tags" => [
                            "type" => 'json',
                            "comment" => 'Тэги',
                            "nullable" => true,
                            "example" => ""
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string == '/rentauto/tariffs') {
                $model = 'Tariff';
                $table = "tariff";
                $titleMenu = 'Тарифы';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "отчете",   // Измененяет информацию о
                        "many_r" => "отчетов", // возвращает список
                        "one" => "отчет",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Первый',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "type_id" => [
                            "type" => 'integer',
                            "comment" => 'Тип тарифа',
                            "nullable" => true,
                        ],
                        "price" => [
                            "type" => 'integer',
                            "comment" => 'Цена',
                            "nullable" => true,
                        ],
                        "cnt" => [
                            "type" => 'integer',
                            "comment" => 'Кол-во использований',
                            "nullable" => true,
                            "example" => "15"
                        ],
                        "params" => [
                            "type" => 'json',
                            "comment" => 'Параметры',
                            "nullable" => true,
                            "example" => ""
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string = '/rentauto/statuses') {
                $model = 'Status';
                $table = "statuses";
                $titleMenu = 'Статусы авто';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "статусе",   // Измененяет информацию о
                        "many_r" => "статусов", // возвращает список
                        "one" => "статус",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Первый',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------


        }
        if ($module == 'Apps') {
            //----------------------------------
            if ($string == '/apps/domain') {
                $model = 'Domain';
                $table = "domains";
                $titleMenu = 'Домены';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "домене",   // Измененяет информацию о
                        "many_r" => "доменов", // возвращает список
                        "one" => "домен",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Volvo XC60',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "url" => [
                            "type" => 'string',
                            "comment" => 'URL',
                            "nullable" => true,
                        ],
                        "is_bisy" => [
                            "type" => 'integer',
                            "comment" => 'Занят',
                            "nullable" => true,
                        ],
                        "type_id" => [
                            "type" => 'integer',
                            "comment" => 'тип домена',
                            "nullable" => true,
                        ],
                        "team_id" => [
                            "type" => 'string',
                            "comment" => 'Команда',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string == '/apps/typeapp') {
                $model = 'TypeApp';
                $table = "type_apps";
                $titleMenu = 'Типы приложений';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "типе приложений",   // Измененяет информацию о
                        "many_r" => "типов приложений", // возвращает список
                        "one" => "тип приложений",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Основное',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/sources') {
                $model = 'Source';
                $table = "sources";
                $titleMenu = 'Источники';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "источники",   // Измененяет информацию о
                        "many_r" => "источников", // возвращает список
                        "one" => "источник",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/lang') {
                $model = 'Lang';
                $table = "lang";
                $titleMenu = 'Языки программирования';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "языке программирования",   // Измененяет информацию о
                        "many_r" => "языков программирования", // возвращает список
                        "one" => "язык программирования",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/category') {
                $model = 'Category';
                $table = "categories";
                $titleMenu = 'Категории';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "категории",   // Измененяет информацию о
                        "many_r" => "категорий", // возвращает список
                        "one" => "категорию",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/categoryapp') {
                $model = 'CategoryApp';
                $table = "category_apps";
                $titleMenu = 'Категории приложений';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "категории приложений",   // Измененяет информацию о
                        "many_r" => "категорий приложений", // возвращает список
                        "one" => "категорию приложения",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/privacylevel') {
                $model = 'PrivacyLevel';
                $table = "privacy_levels";
                $titleMenu = 'Уровень приватности';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "уровне приватности",   // Измененяет информацию о
                        "many_r" => "уровней приватности", // возвращает список
                        "one" => "уровень приватности",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/age') {
                $model = 'Age';
                $table = "ages";
                $titleMenu = 'Возрасты';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "возрасте",   // Измененяет информацию о
                        "many_r" => "возрастов", // возвращает список
                        "one" => "возраст",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/androidversion') {
                $model = 'AndroidVersion';
                $table = "android_versions";
                $titleMenu = 'Версии Android';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "версии android",   // Измененяет информацию о
                        "many_r" => "версий android", // возвращает список
                        "one" => "версию android",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/typeproxy') {
                $model = 'TypeProxy';
                $table = "type_proxies";
                $titleMenu = 'Типы прокси';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "типе прокси",   // Измененяет информацию о
                        "many_r" => "типов прокси", // возвращает список
                        "one" => "тип прокси",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => ' ',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string == '/apps/logs') {
                $model = 'Log';
                $table = "apps.logs";
                $titleMenu = 'Логи';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "логах",   // Измененяет информацию о
                        "many_r" => "логов", // возвращает список
                        "one" => "лог",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "user_id" => [
                            "type" => 'integer',
                            "comment" => 'Пользователь',
                            "nullable" => true,
                            'example' => '1',
                        ],
                        "app_id" => [
                            "type" => 'integer',
                            "comment" => 'Приложение',
                            "nullable" => true,
                        ],
                        "fb_user" => [
                            "type" => 'string',
                            "comment" => 'Пользователь FB',
                            "nullable" => true,
                        ],
                        "responce" => [
                            "type" => 'json',
                            "comment" => 'Пользователь FB',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],
                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$spr->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>3,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
        }
        if ($module == 'Ads') {
            //----------------------------------
            if ($string == '/ads/currency') {
                $model = 'Currency';
                $table = "currencies";
                $titleMenu = 'Валюты';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "валюте",   // Измененяет информацию о
                        "many_r" => "валют", // возвращает список
                        "one" => "валюту",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Рубль',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$adminMenu->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
                    ]
                ];
            } //Done
            //----------------------------------
            if ($string = '/ads/command') {
                $model = 'Command';
                $table = "commands";
                $titleMenu = 'Команды';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "команде",   // Измененяет информацию о
                        "many_r" => "команд", // возвращает список
                        "one" => "команду",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Команда',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$adminMenu->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
        }
        if ($module == 'Push') {
            //----------------------------------
            if ($string == '/push/push') {
                $model = 'Push';
                $table = "push";
                $titleMenu = 'Пуши';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "пуше",   // Измененяет информацию о
                        "many_r" => "пушей", // возвращает список
                        "one" => "пуш",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Volvo XC60',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "title" => [
                            "type" => 'string',
                            "comment" => 'Заголовок',
                            "nullable" => true,
                        ],
                        "message" => [
                            "type" => 'string',
                            "comment" => 'Сообщение',
                            "nullable" => true,
                        ],
                        "link" => [
                            "type" => 'string',
                            "comment" => 'Ссылка',
                            "nullable" => true,
                        ],
                        "image" => [
                            "type" => 'string',
                            "comment" => 'Картинка',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string == '/push/tag') {
                $model = 'Tag';
                $table = "tags";
                $titleMenu = 'Теги';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "теге",   // Измененяет информацию о
                        "many_r" => "тегов", // возвращает список
                        "one" => "тег",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Volvo XC60',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string == '/push/pushlog') {
                $model = 'PushLog';
                $table = "push_logs";
                $titleMenu = 'Логи пушей';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "логе пуша",   // Измененяет информацию о
                        "many_r" => "логов пушей", // возвращает список
                        "one" => "лог пуша",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "date" => [
                            "type" => 'date',
                            "comment" => 'Дата',
                            "nullable" => true,
                        ],
                        "auditory" => [
                            "type" => 'string',
                            "comment" => 'Аудитория',
                            "nullable" => true,
                        ],
                        "geo" => [
                            "type" => 'string',
                            "comment" => 'Гео',
                            "nullable" => true,
                        ],
                        "apps" => [
                            "type" => 'string',
                            "comment" => 'Приложения',
                            "nullable" => true,
                        ],
                        "team" => [
                            "type" => 'string',
                            "comment" => 'Команда',
                            "nullable" => true,
                        ],
                        "request" => [
                            "type" => 'string',
                            "comment" => 'Запрос',
                            "nullable" => true,
                        ],
                        "response" => [
                            "type" => 'string',
                            "comment" => 'Ответ',
                            "nullable" => true,
                        ],
                        "sendpush_id" => [
                            "type" => 'integer',
                            "comment" => 'ID задания',
                            "nullable" => true,
                        ],
                        "owner_id" => [
                            "type" => 'integer',
                            "comment" => 'Владелец',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
        }
        if ($module == 'Clients') {
            //----------------------------------
            if ($string == '/clients/clients') {
                $model = 'Client';
                $table = "clients";
                $titleMenu = 'Клиенты';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "клиенте",   // Измененяет информацию о
                        "many_r" => "клиентов", // возвращает список
                        "one" => "клиента",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            "nullable" => true,
                            'example' => 'Volvo XC60',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 0,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string == '/clients/individual') {
                $model = 'Individual';
                $table = "individuals";
                $titleMenu = 'Физ. лица';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "физ лице",   // Измененяет информацию о
                        "many_r" => "физ. лиц", // возвращает список
                        "one" => "физ. лицо",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'ФИО',
                            "nullable" => true,
                            'example' => 'Иван Иваныч Иванов',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Короткое название',
                            "nullable" => true,
                        ],
                        "firstname" => [
                            "type" => 'string',
                            "comment" => 'Имя',
                            "nullable" => true,
                        ],
                        "middlename" => [
                            "type" => 'string',
                            "comment" => 'Отчество',
                            "nullable" => true,
                        ],
                        "lastname" => [
                            "type" => 'string',
                            "comment" => 'Фамилия',
                            "nullable" => true,
                        ],
                        "phone" => [
                            "type" => 'string',
                            "comment" => 'Телефон',
                            "nullable" => true,
                        ],
                        "email" => [
                            "type" => 'string',
                            "comment" => 'email',
                            "nullable" => true,
                        ],
                        "birthday" => [
                            "type" => 'date',
                            "comment" => 'День рождения',
                            "nullable" => true,
                        ],
                        "passport_seria" => [
                            "type" => 'string',
                            "comment" => 'Серия паспорта',
                            "nullable" => true,
                        ],
                        "passport_number" => [
                            "type" => 'string',
                            "comment" => 'Номер паспорта',
                            "nullable" => true,
                        ],
                        "passport_date" => [
                            "type" => 'date',
                            "comment" => 'Когда выдан',
                            "nullable" => true,
                        ],
                        "passport_kem" => [
                            "type" => 'string',
                            "comment" => 'Кем выдан паспорт',
                            "nullable" => true,
                        ],
                        "passport_code" => [
                            "type" => 'string',
                            "comment" => 'Код подразделения',
                            "nullable" => true,
                        ],
                        "address_reg" => [
                            "type" => 'string',
                            "comment" => 'Адрес регистрации',
                            "nullable" => true,
                        ],
                        "vodud_date" => [
                            "type" => 'date',
                            "comment" => 'Дата выдачи вод. удостоверения',
                            "nullable" => true,
                        ],
                        "vodud_nomer" => [
                            "type" => 'string',
                            "comment" => 'Номер вод. удостоверения',
                            "nullable" => true,
                        ],
                        "user_id" => [
                            "type" => 'integer',
                            "comment" => 'Пользователь',
                            "nullable" => true,
                        ],
                        "manager_id" => [
                            "type" => 'integer',
                            "comment" => 'Ответственный менеджер',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------
            if ($string == '/clients/company') {
                $model = 'Company';
                $table = "companies";
                $titleMenu = 'Компании';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "компании",   // Измененяет информацию о
                        "many_r" => "компаний", // возвращает список
                        "one" => "компанию",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'Название',
                            'example' => 'Название компании',
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Логин',
                            'example' => 'логин компании',
                        ],
                        "logo" => [
                            "type" => 'string',
                            "comment" => 'Логотип',
                            "nullable" => true,
                            'example' => 'logo.png',
                        ],
                        "account_image" => [
                            "type" => 'string',
                            "comment" => 'Изображение аккаунта',
                            "nullable" => true,
                            'example' => 'image.png',
                        ],
                        "fullname" => [
                            "type" => 'string',
                            "comment" => 'Полное название',
                            "nullable" => true,
                            'example' => 'полное название компании',
                        ],
                        "ogrn" => [
                            "type" => 'string',
                            "comment" => 'ОГРН',
                            "nullable" => true,
                            'example' => 'ОГРН',
                        ],
                        "phone" => [
                            "type" => 'string',
                            "comment" => 'Телефон',
                            "nullable" => true,
                            'example' => '+170 898 55 555',
                        ],
                        "email" => [
                            "type" => 'string',
                            "comment" => 'email',
                            "nullable" => true,
                            'example' => 'company@cm.ru',
                        ],
                        "site" => [
                            "type" => 'string',
                            "comment" => 'Сайт',
                            "nullable" => true,
                            'example' => '://company.loc',
                        ],
                        "inn" => [
                            "type" => 'string',
                            "comment" => 'ИНН',
                            "nullable" => true,
                            'example' => '21233987655',
                        ],
                        "kpp" => [
                            "type" => 'string',
                            "comment" => 'КПП',
                            "nullable" => true,
                            'example' => 'кпп',
                        ],
                        "okpo" => [
                            "type" => 'string',
                            "comment" => 'ОКПО',
                            "nullable" => true,
                            'example' => 'окпо',
                        ],
                        "director_fio" => [
                            "type" => 'string',
                            "comment" => 'ФИО директора',
                            "nullable" => true,
                            'example' => 'Иванов Иван Иванович',
                        ],
                        "director_position" => [
                            "type" => 'string',
                            "comment" => 'Должность директора',
                            "nullable" => true,
                            'example' => 'директор',
                        ],
                        "company_src" => [
                            "type" => 'json',
                            "comment" => 'Данные по компании',
                            "nullable" => true,
                            'example' => '[данные1, данные2]',
                        ],
                        "bank_src" => [
                            "type" => 'json',
                            "comment" => 'Данные по банку',
                            "nullable" => true,
                            'example' => '[данные1, данные2]',
                        ],
                        "bank" => [
                            "type" => 'string',
                            "comment" => 'Банк',
                            "nullable" => true,
                            'example' => 'Тинькофф',
                        ],
                        "bik" => [
                            "type" => 'string',
                            "comment" => 'БИК',
                            "nullable" => true,
                            'example' => 'TNF9055',
                        ],
                        "korr_schet" => [
                            "type" => 'string',
                            "comment" => 'Корр. счет',
                            "nullable" => true,
                            'example' => 'T8704355F9000',
                        ],
                        "rasch_schet" => [
                            "type" => 'string',
                            "comment" => 'Расч. счет',
                            "nullable" => true,
                            'example' => 'RS2355545199044',
                        ],
                        "url_appstore" => [
                            "type" => 'string',
                            "comment" => 'URL приложения в аппсторе',
                            "nullable" => true,
                            'example' => '//appstore.com',
                        ],
                        "url_playmarket" => [
                            "type" => 'string',
                            "comment" => 'URL приложения в Google play',
                            "nullable" => true,
                            'example' => '//google.play.com',
                        ],
                        "timezone" => [
                            "type" => 'string',
                            "comment" => 'Часовой пояс',
                            "nullable" => true,
                            'example' => 'UTC',
                        ],
                        "src" => [
                            "type" => 'json',
                            "comment" => 'Локализация',
                            "nullable" => true,
                            'example' => '[en,ru]',
                        ],
                        "lang" => [
                            "type" => 'string',
                            "comment" => 'Язык приложения',
                            "nullable" => true,
                            'example' => 'ru',
                        ],
                        "valute_code" => [
                            "type" => 'string',
                            "comment" => 'Код валюты',
                            "nullable" => true,
                            'example' => 'RUB',
                        ],
                        "legal_address_id" => [
                            "type" => 'integer',
                            "comment" => 'Юридический адрес',
                            "nullable" => true,
                            'example' => '2',
                        ],
                        "fact_address_id" => [
                            "type" => 'integer',
                            "comment" => 'Фактический адрес',
                            "nullable" => true,
                            'example' => '2',
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => '0-новая,1-активна,2-блокирована',
                            "nullable" => true,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
            //----------------------------------

        }
        if ($module == 'Cabinet') {
            if ($string == '/cabinet/balance') {
                $model = 'Balance';
                $table = "balance";
                $titleMenu = 'Баланс';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "балансе",   // Измененяет информацию о
                        "many_r" => "балансов", // возвращает список
                        "one" => "баланс",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "user_id" => [
                            "type" => 'integer',
                            "comment" => 'ID пользователя',
                            "default" => "1",
                            "example" => "1"
                        ],
                        "balance" => [
                            "type" => 'integer',
                            "comment" => '<Баланс счёта',
                            "default" => "1",
                            "example" => "1"
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => "1",
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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
        if ($module == 'Staff') {
            if ($string == '/staff/candidates') {
                $model = 'Candidat';
                $table = "candidates";
                $titleMenu = 'Кандидаты';
                $modelLower = strtolower($model);
                $moduleLower = strtolower($module);
                $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
                $config[$module][$model] = [
                    "name" => 'api',
                    "model" => $model,
                    // используется в контроллере в описани Апи
                    "descr" => [
                        "about" => "кандидате",   // Измененяет информацию о
                        "many_r" => "кандидатов", // возвращает список
                        "one" => "кандадата",     // Создает
                    ],
                    "table" => $table,
                    "module" => $module,
                    "fields" => [
                        "name" => [
                            "type" => 'string',
                            "comment" => 'ФИО кандидата',
                            "nullable" => true,
                            "example" => "ФИО"
                        ],
                        "shortname" => [
                            "type" => 'string',
                            "comment" => 'Краткое имя',
                            "nullable" => true,
                            "example" => "15"
                        ],
                        "resume_name" => [
                            "type" => 'string',
                            "comment" => 'Наименование резюме',
                            "nullable" => true,
                            "example" => "15"
                        ],
                        "gender" => [
                            "type" => 'integer',
                            "comment" => 'Наименование резюме',
                            "nullable" => true,
                            "example" => "15",
                            "default" => 1

                        ],
                        "ext_created_at" => [
                            "type" => 'string',
                            "comment" => 'Дата создания резюме в HH.ru',
                            "nullable" => true,
                        ],
                        "ext_updated_at" => [
                            "type" => 'string',
                            "comment" => 'Дата обновления резюме в HH.ru',
                            "nullable" => true,
                        ],
                        "url" => [
                            "type" => 'string',
                            "comment" => 'Ссылка на hh.ru на резюме',
                            "nullable" => true,
                        ],
                        "salary" => [
                            "type" => 'integer',
                            "comment" => 'Желаемая ЗП',
                            "nullable" => true,
                        ],
                        "src" => [
                            "type" => 'text',
                            "comment" => 'Дата обновления резюме в HH.ru',
                            "nullable" => true,
                        ],
                        "status" => [
                            "type" => 'integer',
                            "comment" => 'Статус',
                            "default" => 1,
                            "example" => "1"
                        ],
                    ],

                    "generate" => $generateArray,
                    "paths" => [
                        'model' => 'Modules/' . $module . '/Models/' . $model . '.php',
                        'migration' => 'Modules/' . $module . '/Database/Migrations/' . $migration . '.php',
                        'seeder' => 'Modules/' . $module . '/Database/Seeders/' . $model . 'Seeder.php',
                        'database_seeder' => 'Modules/' . $module . '/Database/Seeders/' . $module . 'DatabaseSeeder.php',
                        'menu_seeder' => 'Modules/' . $module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                        'request' => 'Modules/' . $module . '/Http/Requests/' . $model . 'Request.php',
                        'controller' => 'Modules/' . $module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                        'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                        'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => $model::class,
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

        if ($this->string == '/access/services') {
            $model = 'Service';
            $table = "services";
            $titleMenu = 'Сервисы';
            $modelLower = strtolower($model);
            $moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервисе",   // Измененяет информацию о
                    "many_r" => "сервисы", // возвращает список
                    "one" => "сервис",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$this->module\\Models\\$model',
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
                ]
            ];
            $this->string = '/access/accesses';
        }

        if ($this->string == '/access/accesses') {
            $model = 'Access';
            $table = "accesses";
            $titleMenu = 'Доступы';
            $modelLower = strtolower($model);
            $moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "доступе",   // Измененяет информацию о
                    "many_r" => "доступов", // возвращает список
                    "one" => "доступ",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "host" => [
                        "type" => 'string',
                        "comment" => 'Host',
                        "nullable" => true,
                        "example" => "https://example.com"
                    ],
                    "login" => [
                        "type" => 'string',
                        "comment" => 'Имя пользователя',
                        "nullable" => true,
                        "example" => "user"
                    ],
                    "pass" => [
                        "type" => 'string',
                        "comment" => 'Пароль',
                        "nullable" => true,
                        "example" => "password"
                    ],
                    "token" => [
                        "type" => 'text',
                        "comment" => 'Токен',
                        "nullable" => true,
                        "example" => ""
                    ],
                    "descr" => [
                        "type" => 'text',
                        "comment" => 'Описание',
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
                    "group_id" => [
                        "type" => 'integer',
                        "comment" => 'Группа',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "server_id" => [
                        "type" => 'integer',
                        "comment" => 'Сервер',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "src" => [
                        "type" => 'json',
                        "comment" => 'Api',
                        "nullable" => true,
                        "example" => "{json}"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$this->module\\Models\\$model',
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
                ]
            ];
            $this->string = '/access/cards';
        }

        if ($this->string == '/access/cards') {
            $model = 'Card';
            $table = "cards";
            $titleMenu = 'Карты доступа';
            $modelLower = strtolower($model);
            $moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "карте доступа",   // Измененяет информацию о
                    "many_r" => "карт доступа", // возвращает список
                    "one" => "карту доступа",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$this->module\\Models\\$model',
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
                ]
            ];
            $this->string = '/access/accesses_cards';
        }

        if ($this->string == '/access/accesses_cards') {
            $model = 'AccessCard';
            $table = "accesses_cards";
            $titleMenu = 'Доступы и Карты доступа';
            $modelLower = strtolower($model);
            $moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "доступе и карте доступа",   // Измененяет информацию о
                    "many_r" => "доступов и карт доступа", // возвращает список
                    "one" => "доступ и карту доступа",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "card_id" => [
                        "type" => 'integer',
                        "comment" => 'Карта доступа',
                        "nullable" => true,
                        "example" => "1"
                    ],
                    "access_id" => [
                        "type" => 'integer',
                        "comment" => 'Доступ',
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
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$this->module\\Models\\$model',
                    'options'=>'{\"fields\":
              {
              {fields}
              }
              }',
                    'level'=>2,
                ]);"
                ]
            ];
            $this->string = '/access/servers';
        }

        if ($this->string == '/access/servers') {
            $model = 'Server';
            $table = "servers";
            $titleMenu = 'Серверы';
            $modelLower = strtolower($model);
            $moduleLower = strtolower($this->module);
            $migration = date("Y_m_d_His") . "_create_" . strtolower($model) . "_table";
            $this->config[$this->module][$model] = [
                "name" => 'api',
                "model" => $model,
                // используется в контроллере в описани Апи
                "descr" => [
                    "about" => "сервере",   // Измененяет информацию о
                    "many_r" => "серверов", // возвращает список
                    "one" => "сервер",     // Создает
                ],
                "table" => $table,
                "module" => $this->module,
                "fields" => [
                    "name" => [
                        "type" => 'string',
                        "comment" => 'Наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "shortname" => [
                        "type" => 'string',
                        "comment" => 'Краткое наименование',
                        "nullable" => true,
                        "example" => "Доступ"
                    ],
                    "status" => [
                        "type" => 'integer',
                        "comment" => 'Статус',
                        "default" => 0,
                        "nullable" => true,
                        "example" => "1"
                    ],
                ],

                "generate" => $this->generateArray,
                "paths" => [
                    'model' => 'Modules/' . $this->module . '/Models/' . $model . '.php',
                    'migration' => 'Modules/' . $this->module . '/Database/Migrations/' . $migration . '.php',
                    'seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $model . 'Seeder.php',
                    'database_seeder' => 'Modules/' . $this->module . '/Database/Seeders/' . $this->module . 'DatabaseSeeder.php',
                    'menu_seeder' => 'Modules/' . $this->module . '/Database/Seeders/Lists/MenuListsSeeder.php',
                    'request' => 'Modules/' . $this->module . '/Http/Requests/' . $model . 'Request.php',
                    'controller' => 'Modules/' . $this->module . '/Http/Controllers/Api/' . $model . 'Controller.php',
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
                    'swagger' => '*                 @OA\Property(
         *                     property="{name}",
         *                     type="{type}",
         *                     description="{comment}",
         *                     example="{example}"
         *                 ),', // для swagger
                    'menu' => "Menu::create([
                    'name' => '$titleMenu',
                    'shortname' => '$modelLower',
                    'parent_id' => \$chapter->id,
                    'is_api' => 2,      // для генерации апи
                    'page' => '/web/$moduleLower/$modelLower',
                    'icon' => 'uil uil-user',
                    'api' => '/api/$moduleLower/$modelLower',
                    'model' => '\\Modules\\$this->module\\Models\\$model',
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

}
