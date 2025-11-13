<?php

namespace App\View\Components;

use App\Models\Menu as MenuModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Illuminate\View\View;

class Menu extends Component
{
    public ?int $root;
    public ?string $active;

    public function __construct(?int $root = null, ?string $active = null)
    {
        $this->root   = $root;
        $this->active = $active ?: request()->path();
    }

    public function render(): View
    {
        return view('components.menu', [
            'items'  => $this->getTree($this->root),
            'active' => $this->active,
        ]);
    }

    protected function getTree(?int $rootId = null): array
    {
        $root=\App\Models\Menu::query()->where('shortname', 'messenger_module')->first();
        $rootId = $root->id;
        $key = 'menu.tree.'.($rootId ?? 'root');

        return Cache::remember($key, now()->addMinutes(10), function () use ($rootId) {
            $all = MenuModel::active()
                ->when($rootId, fn($q) => $q->where(fn($qq) => $qq->where('id',$rootId)->orWhere('parent_id',$rootId)))
                ->orderBy('level')->orderBy('nom')->orderBy('id')
                ->get();

            //dd($all);

            if ($rootId && !$all->firstWhere('id',$rootId)) {
                if ($root = MenuModel::active()->find($rootId)) $all->prepend($root);
            }

            return $this->buildTree($all);
        });
    }

    protected function buildTree(Collection $items): array
    {
        $map = [];
        foreach ($items as $m) {
            $map[$m->id] = [
                'id'   => $m->id,
                'name' => $m->name ?? $m->shortname ?? ('#'.$m->id),
                'icon' => $m->icon,
                'url'  => $this->makeUrl($m),
                'badge'=> data_get($m->options, 'badge'),
                'raw'  => $m,
                'children' => [],
                'parent_id'=> (int)($m->parent_id ?? 0),
            ];
        }

        $roots = [];
        foreach ($map as $id => &$node) {
            if ($node['parent_id'] && isset($map[$node['parent_id']])) {
                $map[$node['parent_id']]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        return $roots;
    }

    protected function makeUrl($menu): string
    {
        $page = (string)($menu->page ?? '');
        if (!$page) return '#';
        try {
            if (app('router')->has($page)) return route($page);
        } catch (\Throwable $e) {}
        return url($page);
    }
}
