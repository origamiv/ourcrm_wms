<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use OurCRM\BaseModel;

/**
{$property}
 */
class DummyClass extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    //public $guarded = ['id'];
    protected $table='DummyModuleLower.DummyTable';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
     protected $fillable = [
        //{$fillable}
     ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

}
