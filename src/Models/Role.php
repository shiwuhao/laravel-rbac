<?php

namespace Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Rbac\Enums\GuardType;
use Rbac\Contracts\RoleContract;

/**
 * 角色模型
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property GuardType $guard_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Role extends Model implements RoleContract
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'guard_name',
    ];

    protected $casts = [
        'guard_name' => GuardType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 获取表名
     */
    public function getTable(): string
    {
        return config('rbac.tables.roles', parent::getTable());
    }

    /**
     * 权限关联
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.permission'),
            config('rbac.tables.role_permission'),
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * 用户关联
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('rbac.tables.user_role'),
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * 检查角色是否具有指定权限
     */
    public function hasPermission(string|Permission $permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions()
                ->where('slug', $permission)
                ->exists();
        }

        return $this->permissions()
            ->where('id', $permission->id)
            ->exists();
    }

    /**
     * 检查角色是否具有任一权限
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $slugs = collect($permissions)->map(function ($permission) {
            return is_string($permission) ? $permission : $permission->slug;
        })->toArray();

        return $this->permissions()
            ->whereIn('slug', $slugs)
            ->exists();
    }

    /**
     * 检查角色是否具有所有权限
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $slugs = collect($permissions)->map(function ($permission) {
            return is_string($permission) ? $permission : $permission->slug;
        })->toArray();

        $hasCount = $this->permissions()
            ->whereIn('slug', $slugs)
            ->count();

        return $hasCount === count($slugs);
    }

    /**
     * 分配权限给角色
     */
    public function givePermission(string|array|Permission $permissions): self
    {
        $permissions = collect(\Arr::wrap($permissions))
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return Permission::where('slug', $permission)->first();
                }
                return $permission;
            })
            ->filter()
            ->pluck('id');

        $this->permissions()->syncWithoutDetaching($permissions);

        return $this;
    }

    /**
     * 撤销角色权限
     */
    public function revokePermission(string|array|Permission $permissions): self
    {
        $permissions = collect(\Arr::wrap($permissions))
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return Permission::where('slug', $permission)->first();
                }
                return $permission;
            })
            ->filter()
            ->pluck('id');

        $this->permissions()->detach($permissions);

        return $this;
    }

    /**
     * 同步角色权限
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)
            ->map(function ($permission) {
                if (is_string($permission)) {
                    return Permission::where('slug', $permission)->first()?->id;
                }
                return $permission instanceof Permission ? $permission->id : $permission;
            })
            ->filter();

        $this->permissions()->sync($permissionIds);

        return $this;
    }

    /**
     * 根据名称查找角色
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * 根据标识符查找角色
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * 根据守卫查找角色
     */
    public function scopeByGuard($query, string|GuardType $guard)
    {
        $guardValue = $guard instanceof GuardType ? $guard->value : $guard;
        return $query->where('guard_name', $guardValue);
    }

    /**
     * 创建工厂
     */
    protected static function newFactory()
    {
        return \Rbac\Database\Factories\RoleFactory::new();
    }
}