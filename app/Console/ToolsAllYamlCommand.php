<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ToolsAllYamlCommand extends Command
{
    protected $signature = 'tools:all_yaml {module?}';

    protected $description = 'Создает YAML файлы для всех таблиц БД';

    public function handle()
    {
        $this->info('Старт');

        $module = 'messenger'; //'$this->argument('module') ?? ucfirst(env('MODULE'));
        $schema = strtolower($module);

        $tables = DB::connection('two')->query()
            ->from('information_schema.tables')
            ->where('table_catalog', env('DB_DATABASE2'))
            ->where('table_schema', $schema)
            ->get();

        foreach ($tables as $table) {
            $model = ucfirst(Str::singular($table->table_name));
            Artisan::call('tools:yaml', [
                'table' => $table->table_name,
                'model' => $model,
                'module' => $module,
                '--withMenu' => true,
            ], $this->output);
        }

        $tableNames = $tables->map(fn($table) => $table->table_name)->toArray();

        $menuNames = $this->getMenus($module);
        $menuNames = $menuNames->filter(fn($menuName) => !in_array(Str::plural($menuName), $tableNames));
        foreach ($menuNames as $menuName) {
            $tableName = Str::plural($menuName);
            Artisan::call('tools:yaml', [
                'table' => $tableName,
                'model' => $menuName,
                '--withMenu' => true,
            ], $this->output);
        }

        $this->info('Завершение');
    }

    private function getMenus(string $module)
    {
        $moduleLowercase = strtolower($module);

        $parentMenu = DB::connection('two')->table('lists.menus')
            ->where('shortname', "{$moduleLowercase}_module")
            ->first();

        if (!$parentMenu) {
            return collect();
        }

        $menus = DB::connection('two')->table('lists.menus')
            ->where('parent_id', $parentMenu->id)
            ->get();

        $childMenus = DB::connection('two')->table('lists.menus')
            ->whereIn('parent_id', $menus->pluck('id')->toArray())
            ->get();
        while ($childMenus->count()) {
            $menus = $menus->merge($childMenus);
            $childMenus = DB::connection('two')->table('lists.menus')
                ->whereIn('parent_id', $childMenus->pluck('id')->toArray())
                ->get();
        }

        $menuShortnames = $menus->pluck('shortname');

        $menuNames = $menuShortnames->map(function (string $shortname) {
            return Arr::last(explode('.', $shortname));
        });

        return $menuNames;
    }

    /**
     * @param array $config
     * @return array
     */
    private function fillableForModels(array $config): array
    {
        $fillableFields = [];
        $templateFillable = $config['templates']['fillable'];

        foreach ($config['fields'] as $fieldName => $field) {
            $fillable = $templateFillable;
            $comment = (!empty($field['comment'])) ? $field['comment'] : '';
            $fillable = str_replace('{name}', $fieldName, $fillable);
            $fillable = str_replace('{comment}', $comment, $fillable);
            $fillableFields[] = $fillable;
        }

        return $fillableFields;
    }
}
