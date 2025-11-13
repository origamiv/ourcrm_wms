<?php

namespace Modules\Lists\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property integer $parent_id Родительский раздел
 * @property integer $level Уровень вложенности
 * @property string $page Страница
 * @property string $api API
 * @property json $options Опции
 * @property json $settings Опции
 * @property integer $status Статус
 */
class Menu extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    public $guarded = ['id'];
    protected $table;
    protected $cashFor = null;

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function __construct()
    {
        parent::__construct();
        $this->table =config('app.menu_from').'.menus';
    }

    protected function options(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => !empty($value) ? json_decode($value) : null,
        );
    }
    protected function settings(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => !empty($value) ? json_decode($value) : null,
        );
    }

    public function childs()
    {
        return Menu::query()->where('parent_id', '=', $this->id)->get();
    }
    public function getChildCountAttribute()
    {
        return Menu::query()->where('parent_id', '=', $this->id)->count();
    }
}
