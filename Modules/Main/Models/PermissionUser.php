<?php

namespace Modules\Main\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property integer $permission_id Право пользователя
 * @property integer $user_id Пользователь
 * @property integer $status Статус
 */
class PermissionUser extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    //public $guarded = ['id'];
    protected $table = 'main.permission_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'permission_id',// Право пользователя
        'user_id',// Пользователь
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
        return Permission::find($this->permission_id);
    }

    public function getUserAttribute()
    {
        return User::find($this->user_id);
    }
}
