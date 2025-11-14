<?php

namespace Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Rbac\Enums\GuardType;
use Rbac\Contracts\RoleContract;
use Rbac\Contracts\PermissionContract;

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
     * 数据范围关联
     */
    public function dataScopes(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.data_scope'),
            config('rbac.tables.role_data_scope'),
            'role_id',
            'data_scope_id'
        )->withPivot('constraint')->withTimestamps();
    }

    /**
     * 检查角色是否具有指定权限
     */
    public function hasPermission(string|PermissionContract $permission): bool
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
    public function givePermission(string|array|PermissionContract $permissions): self
    {
        $permissionModel = config('rbac.models.permission');
        
        $permissions = collect(\Arr::wrap($permissions))
            ->map(function ($permission) use ($permissionModel) {
                if (is_string($permission)) {
                    return $permissionModel::where('slug', $permission)->first();
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
    public function revokePermission(string|array|PermissionContract $permissions): self
    {
        $permissionModel = config('rbac.models.permission');
        
        $permissions = collect(\Arr::wrap($permissions))
            ->map(function ($permission) use ($permissionModel) {
                if (is_string($permission)) {
                    return $permissionModel::where('slug', $permission)->first();
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
        $permissionModel = config('rbac.models.permission');
        
        $permissionIds = collect($permissions)
            ->map(function ($permission) use ($permissionModel) {
                if (is_string($permission)) {
                    return $permissionModel::where('slug', $permission)->first()?->id;
                }
                return $permission instanceof PermissionContract ? $permission->id : $permission;
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