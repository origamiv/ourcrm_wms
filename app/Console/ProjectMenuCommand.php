<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Yaml\Package\Yaml;

class ProjectMenuCommand extends Command
{
    public $menu_table = 'lists.menus';

    /**
     * @var string
     */
    protected $signature = 'project:menu';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Обновление меню системы';

    /**
     * Название текущего модуля
     *
     * @var string
     */
    protected readonly string $module;

    public function __construct()
    {
        parent::__construct();
        $this->menu_table=config('app.menu_from').'.menus';
        $this->module = module();
    }

    public function handle(): void
    {
        (new Info($this->output))->render("Creating menu for module \"$this->module\"");

        // Удаление существующих меню для модуля
        $parentIds = DB::table($this->menu_table)->where('resource', "{$this->module}_module")->pluck('id');
        while (count($parentIds)) {
            DB::table($this->menu_table)->whereIn('id', $parentIds)->delete();
            $parentIds = DB::table($this->menu_table)->whereIn('parent_id', $parentIds)->pluck('id');
        }

        // Создание родительского меню для модуля
        $parentMenu = $this->createParentMenu();

        //dd($parentMenu);

        // Получение таблиц
        $disk = Storage::build([
            'driver' => 'local',
            'root' => app_path('Config/tables'),
        ]);
        $tables = $disk->files();

        // Создание меню под каждую табличку
        collect($tables)->map(function ($tableFilename) use ($parentMenu) {
            $this->handleTable($parentMenu, $tableFilename);
        });

        // Запуск создания прав
        Artisan::call('project:sync_permissions', outputBuffer: $this->output);
    }

    private function createParentMenu(): object
    {
        $parentMenu = $this->updateOrCreateMenu([
            'shortname' => "{$this->module}_module",
        ], [
            ...config('app.module.main_menu_options'),
            'name' => config('app.module.title'),
            'resource' => "{$this->module}_module",
        ]);

        return $parentMenu;
    }

    private function handleTable(object $parentMenu, string $tableFilename): void
    {
        $yaml = new Yaml;
        $yamlStructure = $yaml->parseFile(app_path('Config/tables/' . $tableFilename));

        $tableName = $yamlStructure['table'] ?? $yamlStructure['menu']['shortname'];

        (new Task($this->output))->render($tableName, function () use ($parentMenu, $yamlStructure) {
            // Если в корне нет поля menuOptions, то меню не создается
            if (!isset($yamlStructure['menu']) || !is_array($yamlStructure['menu'])) {
                return;
            }

            dump($yamlStructure);

            if (isset($yamlStructure['menu']['settings'])) {
                $yamlStructure['menu']['settings'] = json_encode(
                    $yamlStructure['menu']['settings'],
                    JSON_UNESCAPED_UNICODE
                );
            }

            if (isset($yamlStructure['menu']['parent_shortname'])) {
                $parentMenu = $this->getMenuByShortname($yamlStructure['menu']['parent_shortname'], $parentMenu);
            }

            $this->updateOrCreateMenu([
                'shortname' => $yamlStructure['menu']['shortname'],
            ], [
                ...$yamlStructure['menu'],
                'parent_id' => $parentMenu?->id,
            ]);
        });
    }

    private function getMenuByShortname(string $shortname, object $parentMenu): ?object
    {
        $shortnameWithoutModule = Arr::last(explode('.', $shortname));
        $shortnameWithModule = "$this->module.$shortnameWithoutModule";

        $menu = DB::table($this->menu_table)
            ->where('shortname', $shortnameWithModule)
            ->first();

        if ($menu) {
            return $menu;
        }

        // TODO: create from yaml
        $yamlPath = app_path("Config/tables/$shortnameWithoutModule.yaml");

        $menu = $this->createMenuFromYaml($yamlPath, $parentMenu);

        return $menu;
    }

    private function createMenuFromYaml(string $yamlPath, object $parentMenu): ?object
    {
        $yaml = new Yaml;
        $yamlStructure = $yaml->parseFile($yamlPath);

        // Если в корне нет поля menu, то меню не создается
        if (!isset($yamlStructure['menu']) || !is_array($yamlStructure['menu'])) {
            return null;
        }

        if (isset($yamlStructure['menu']['settings'])) {
            $yamlStructure['menu']['settings'] = json_encode(
                $yamlStructure['menu']['settings'],
                JSON_UNESCAPED_UNICODE
            );
        }

        if (isset($yamlStructure['menu']['parent_shortname'])) {
            $parentMenu = $this->getMenuByShortname($yamlStructure['menu']['parent_shortname'], $parentMenu);
        }

        $menu = $this->updateOrCreateMenu([
            'shortname' => $yamlStructure['menu']['shortname'],
        ], [
            ...$yamlStructure['menu'],
            'parent_id' => $parentMenu?->id,
        ]);

        return $menu;
    }

    private function updateOrCreateMenu(array $attributes, array $values): object
    {

        $existing = DB::table($this->menu_table)->where($attributes)->first();

        unset($values['parent_shortname']);

        if ($existing) {
            DB::table($this->menu_table)
                ->where('id', $existing->id)
                ->update($values);
            return DB::table($this->menu_table)->where('id', $existing->id)->first();
        } else {
            DB::table($this->menu_table)->insert(array_merge($attributes, $values));
            return DB::table($this->menu_table)->where($attributes)->first();
        }
    }
}
