<?php

use App\Models\Worker;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Http\Request;

new class extends Component {
    use Toast;

    #[\Livewire\Attributes\Url(as: 'nn')]
    public string $search = '';

    #[\Livewire\Attributes\Url]
    public string $id = '';

    #[Session]
    public $menu_id;

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public $request;


    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete($id): void
    {
        $this->warning("Will delete #$id", 'It is fake.', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        $page = $_SERVER['REQUEST_URI'];
        $menu = \Modules\Lists\Models\Menu::query()->where('page', $page)->first();
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $modelName = str_replace('Modules\\Messenger', 'App', $menu->model);
        $model = new $modelName();

        $fields = $model->getFillable();
        $headers = [];
        $header = ['key' => 'id', 'label' => '#', 'class' => 'w-1'];
        $headers[] = $header;
        foreach ($fields as $field) {
            $headers[] = ['key' => $field, 'label' => $field, 'class' => 'w-20'];
        }

        return $headers;

        //return $model->toArray();

//        return [
//            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
//            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
//            ['key' => 'user_id', 'label' => 'Пользователь', 'class' => 'w-20'],
//            ['key' => 'rate', 'label' => 'Ставка', 'class' => 'w-20'],
//            ['key' => 'is_koef_nalog', 'label' => 'Учет налога', 'class' => 'w-20'],
//            ['key' => 'is_koef_iter', 'label' => 'Учет итерации', 'class' => 'w-20'],
//            ['key' => 'calc_method_id', 'label' => 'Метогд расчета', 'class' => 'w-20'],
//            ['key' => 'status', 'label' => 'Статус', 'sortable' => false],
//        ];
    }

    /**
     * For demo purpose, this is a static collection.
     *
     * On real projects you do it with Eloquent collections.
     * Please, refer to maryUI docs to see the eloquent examples.
     */
    public function items(): Collection
    {
        $page = $_SERVER['REQUEST_URI'];
        $menu = \Modules\Lists\Models\Menu::query()->where('page', $page)->first();
        $modelName = str_replace('Modules\\Messenger', 'App', $menu->model);

        $model=new $modelName();
        //dd($model);
        $r= $model::query()
            //->where('status',1)
            ->limit(3)
            ->get();
        return $r;
    }

    public function with(): array
    {
        $r = session();
        $this->menu_id = $r->get('menu_id');

        //dd($this->items());

        return [
            //'id' =>$this->id,
            'items' => $this->items(),
            'headers' => $this->headers()
        ];
    }

//    public function render(): mixed
//    {
//        return view('livewire.table.index', [
//            'users' => Worker::query()->where('name','like',$this->search.'%')->get(),
//        ]);
//    }
}; ?>

<div>
    <!-- HEADER -->
    @php
        $page=$_SERVER['REQUEST_URI'];
        $menu=\Modules\Lists\Models\Menu::query()->where('page',$page)->first();

        //dd($menu);
    @endphp

    <x-header title="{{$menu->name}}" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass"/>
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel"/>
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card shadow>
        <x-table :headers="$headers" :rows="$items" :sort-by="$sortBy">
            @scope('actions', $item)
            <x-button icon="o-trash" wire:click="delete({{ $item['id'] }})" wire:confirm="Are you sure?" spinner class="btn-ghost btn-sm text-error" />
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" @keydown.enter="$wire.drawer = false" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
<?php
