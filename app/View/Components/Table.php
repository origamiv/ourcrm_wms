<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Table extends Component
{
    /**
     * Заголовок таблицы.
     */
    public string $title;

    /**
     * Массив колонок: [['key'=>'id', 'label'=>'ID'], ...]
     */
    public array $columns;

    /**
     * Массив данных: [['id'=>1, 'name'=>'...'], ...]
     */
    public array $rows;

    public function __construct(string $title = '', array $columns = [], array $rows = [])
    {
        $this->title   = $title;
        $this->columns = $columns;
        $this->rows    = $rows;
    }

    public function render(): View
    {
        return view('components.table');
    }
}
