<?php

namespace Modules\DummyModule\Database\Seeders\Lists;

use Modules\Lists\Models\Menu;
use Modules\Main\Database\Seeders\OnetimeSeeder;

class MenuListsSeeder extends OnetimeSeeder
{
    public function run()
    {
        $chapter = Menu::query()->updateOrCreate(
            ['shortname' => 'module.{{ module_lower }}',],
            [
                'name' => 'Платежи',
                'shortname' => 'module.{{ module_lower }}',
                'resource' => '{{ module_lower }}_module',
                'parent_id' => 0,
                'page' => '/web/{{ module_lower }}',
                'icon' => 'uil uil-money-bill',
            ]);

        //{$menu}


    }
}
