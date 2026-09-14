<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Error;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\Task;
use Modules\Lists\Models\Menu;
use Modules\Main\Models\Permission;
use Modules\Main\Models\Role;

class ProjectSyncPermissionsCommand extends Command
{
    protected $signature = 'project:sync_permissions';

    protected $description = 'Синхронизация прав для поле меню, у которых is_api = 1';

    /**
     * Название текущего модуля
     *
     * @var string
     */
    protected readonly string $module;

    public function __construct()
    {
        parent::__construct();
        $this->module = module();
    }

    public function handle(): void
    {
        (new Info($this->output))->render("Creating permissions for module \"$this->module\"");

        // В старых базах системная роль называлась service, в WMS используется admin.
        $adminRole = Role::whereIn('slug', ['service', 'admin'])->where('status', 1)->whereNull('deleted_at')->first();

        if (!$adminRole) {
            (new Error($this->output))->render('Не найдена активная системная роль service или admin.');
            return;
        }

        $menus = Menu::query()
            ->where('is_api', 2)
            ->where('resource', $this->module)
            ->get();

        /** @var Menu $menu */
        foreach ($menus as $menu) {
            (new Task($this->output))->render($menu->shortname, function () use ($menu, $adminRole) {
                $this->createPermissionsForMenu($menu, $adminRole);
            });
        }
    }

    private function createPermissionsForMenu(Menu $menu, Role $adminRole): void
    {
        $resource = last(explode('.', $menu->shortname));

        $permissions = [
            [
                'name' => 'Может посмотреть список ' . $menu->name,
                'slug' => $resource . '.index',
                'resource' => $resource,
                'system' => false
            ],
            [
                'name' => 'Может добавить ' . $menu->name,
                'slug' => $resource . '.create',
                'resource' => $resource,
                'system' => false
            ],
            [
                'name' => 'Может просматривать инфо о ' . $menu->name,
                'slug' => $resource . '.show',
                'resource' => $resource,
                'system' => false
            ],
            [
                'name' => 'Может редактировать инфо о ' . $menu->name,
                'slug' => $resource . '.update',
                'resource' => $resource,
                'system' => false
            ],
            [
                'name' => 'Может удалить ' . $menu->name,
                'slug' => $resource . '.delete',
                'resource' => $resource,
                'system' => false
            ],
        ];

        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate([
                'slug' => $permData['slug'],
            ], [
                'name' => $permData['name'],
            ]);

            $permissionAttached = $adminRole
                ->permissions()
                ->where('permissions.id', $permission->id)
                ->exists();

            if (!$permissionAttached) {
                $adminRole->permissions()->attach($permission->id);
            }
        }
    }
}
