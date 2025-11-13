<?php

namespace Modules\Main\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends \Yajra\Acl\Models\Permission
{
    use SoftDeletes;

    protected $table='main.permissions';

    public $guarded = ['id'];

    protected static $flushCacheOnUpdate = true;
    /**
     * Specify the amount of time to cache queries.
     * Do not specify or set it to null to disable caching.
     *
     * @var int|\DateTime
     */
    public $cacheFor = null; //1 minute


    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function __construct()
    {
//        $prefix=str_replace('.','_', $this->table);
//        $this->cachePrefix=$prefix."_";
//        $this->cacheTags=[$prefix];
    }

}
