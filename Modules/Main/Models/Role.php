<?php

namespace Modules\Main\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Yajra\Acl\Models\Permission;
use Yajra\Acl\Traits\HasPermission;
use Yajra\Acl\Traits\RefreshPermissionsCache;

/**
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property bool $system
 */
class Role extends \Yajra\Acl\Models\Role
{
    use HasPermission, RefreshPermissionsCache, SoftDeletes;

    /** @var string */
    protected $table = 'main.roles';

    /** @var string[] */
    protected $fillable = ['name', 'company_id', 'slug', 'description', 'system', 'tags'];

    /** @var array<string, string> */
    protected $casts = [
        'system' => 'bool',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Find a role by slug.
     *
     * @param  string  $slug
     * @return \Illuminate\Database\Eloquent\Model|static
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findBySlug(string $slug): \Yajra\Acl\Models\Role
    {
        return static::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Roles can belong to many users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        /** @var class-string $model */
        $model = config('acl.user', config('auth.providers.users.model'));

        return $this->belongsToMany($model)->withTimestamps();
    }

    public function getCountUsersAttribute()
    {
        //dd($this->users()->get());
        return count($this->users()->get());
    }

    public function getPermissionsAttribute()
    {
        $rolePermissionsIds = PermissionRole::query()->where('role_id', '=', $this->id)
            ->get()
            ->pluck('permission_id');

        $permissions = Permission::query()
            ->whereIn('id', $rolePermissionsIds)->get()->pluck('slug');

        return $permissions;
    }

    public function toArray()
    {
        $array = parent::toArray();
        foreach ($this->getMutatedAttributes() as $key)
        {
            if ( ! array_key_exists($key, $array)) {
                $array[$key] = $this->{$key};
            }
        }
        return $array;
    }

    public function permissions(): BelongsToMany
    {
        $model = config('acl.permission', Permission::class);

        return $this->belongsToMany($model, PermissionRole::class)->withTimestamps();
    }
}
