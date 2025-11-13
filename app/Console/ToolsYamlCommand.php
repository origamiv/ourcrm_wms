<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PragmaRX\Yaml\Package\Yaml;

class ToolsYamlCommand extends Command
{
    protected $signature = 'tools:yaml {table} {model} {module} {--withMenu}';

    protected $description = 'Создает YAML файл по текущей таблице БД';

    private array $replace = [];

    public function handle(): void
    {
        //$module = ucfirst(env('MODULE'));

        $module = $this->argument('module'); // Task
        $schema = strtolower($module);

        $table = $this->argument('table'); // tasks
        $model = $this->argument('model'); // Task

        $modelLowercase = strtolower($model);

        $tableName = "$schema.$table";

        (new Info($this->output))->render("Creating .yaml for $tableName");

        $existsMenu = $this->getExistsMenu($tableName, "$schema.$modelLowercase");

        if (DB::connection('two')->getSchemaBuilder()->hasTable($tableName)) {
            $fields = $this->getFields($tableName);

            $arr = [
                'name' => 'api',
                'model' => $model,
                'titleMenu' => $model,
                'descr' => [
                    'about' => 'о ком чем?',
                    'many_r' => 'много кого чего?',
                    'one' => 'редактировать кого чего?',
                ],
                'table' => $table,
                'module' => $module,
                'fields' => $fields,
            ];
        } else {
            $arr = [];
        }

        if ($this->option('withMenu') && !is_null($existsMenu)) {
            $menu = $this->getMenuForYaml($existsMenu);
            unset($menu['parent_id']);
            $arr['menu'] = $menu;
        }

        $yaml = new Yaml();
        $r = $yaml->dump($arr);
        file_put_contents(app_path('Config/tables/' . $table . '.yaml'), $r);
    }

    private function getMenuForYaml(object $existsMenu): array
    {
        $existsMenu->settings = json_decode($existsMenu->settings, true);

        $menu = (array)$existsMenu;
        $menu = array_filter($menu);

        if ($menu['parent_id'] !== $existsMenu->id) {
            $listParentMenu = DB::connection('two')->table('lists.menus')
                ->where('id', $menu['parent_id'])
                ->first();

            if ($listParentMenu && $listParentMenu->id !== $existsMenu->id && $listParentMenu->parent_id !== 0) {
                $listParentMenuArr = $this->getMenuForYaml($listParentMenu);

                $fileName = Arr::last(explode('.', $listParentMenuArr['shortname']));

                unset($listParentMenuArr['parent_id']);

                $yaml = new Yaml();
                $r = $yaml->dump(['menu' => $listParentMenuArr]);
                file_put_contents(app_path('Config/tables/' . $fileName . '.yaml'), $r);

                $menu['parent_shortname'] = $listParentMenuArr['shortname'];
            }
        }

        unset($menu['id']);
        unset($menu['options']);
        unset($menu['created_at']);
        unset($menu['updated_at']);
        unset($menu['deleted_at']);

        return $menu;
    }

    private function clear($str): array|string|null
    {
        $str = str_replace('"', '', $str);
        $str = str_replace("''", '', $str);

        return (!empty($str)) ? $str : null;
    }

    private function convert($type): string
    {
        $z = str_replace('character varying', '', $type);
        if ($z != $type) {
            return 'string';
        }

        $z = str_replace('timestamp', '', $type);
        if ($z != $type) {
            return 'date';
        }

        $z = str_replace('int', '', $type);
        if ($z != $type) {
            return 'integer';
        }

        return $type;
    }

    private function getFields(string $tableName): array
    {
        $fields = [];

        $tableFields = DB::connection('two')->getSchemaBuilder()->getColumns($tableName);

        $ignorableFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($tableFields as $tableField) {
            $field = [];
            $name = $tableField['name'];

            if (in_array($name, $ignorableFields)) {
                continue;
            }

            $type = $this->convert($tableField['type']);
            $comment = $this->clear($tableField['comment']) ?? $tableField['name'];

            $field['type'] = $type;
            $field['comment'] = $comment;
            $field['nullable'] = true;
            $field['example'] = '';

            $fields[$name] = $field;

            (new Task($this->output))->render($name);
        }

        return $fields;
    }

    private function getExistsMenu(...$shortnames)
    {
        $menu = DB::connection('two')->table('lists.menus')
            ->whereIn('shortname', $shortnames)
            ->first();

        return $menu;
    }
}
