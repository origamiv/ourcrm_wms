<?php

namespace Modules\Main\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property integer $role_id Роль
 * @property integer $permission_id Право
 * @property integer $status Статус
 */
class PermissionRole extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    //public $guarded = ['id'];
    protected $table = 'main.permission_role';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',// Роль
        'permission_id',// Право
        'status',// Статус
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function getPermissionAttribute()
    {
        return Permission::query()->where('id', $this->attributes['permission_id'])->first();
    }
    public function getRoleAttribute()
    {
        return Permission::query()->where('id', $this->attributes['role_id'])->first();
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

}
