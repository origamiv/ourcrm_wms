<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    protected $table = 'lists.menus';

    protected $fillable = [
        'name','shortname','parent_id','is_root','is_api','level','page','api','model',
        'options','settings','icon','resource','status','nom','is_list',
    ];

    protected $casts = [
        'options'   => 'array',
        'settings'  => 'array',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
        'deleted_at'=> 'datetime',
    ];

    public const STATUS_ACTIVE = 1;

    public function parent(){ return $this->belongsTo(self::class, 'parent_id'); }
    public function children(){ return $this->hasMany(self::class, 'parent_id')->orderBy('nomer')->orderBy('id'); }

    public function scopeActive($q){
        return $q->where('status', self::STATUS_ACTIVE);
            //->where('module', '=', 'messenger');
    }
}
