<?php

namespace Rbac\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 分配角色 (辅助方法，用于测试)
     */
    public function assignRole($role): self
    {
        $roles = is_array($role) ? $role : [$role];
        $roleIds = collect($roles)->map(function ($r) {
            return is_object($r) ? $r->id : $r;
        });
        $this->roles()->syncWithoutDetaching($roleIds);
        $this->forgetCachedPermissions();
        return $this->fresh(['roles']);
    }

    /**
     * 移除角色 (辅助方法，用于测试)
     */
    public function removeRole($role): self
    {
        $roleId = is_object($role) ? $role->id : $role;
        $this->roles()->detach($roleId);
        $this->forgetCachedPermissions();
        return $this;
    }

    /**
     * 同步角色 (辅助方法，用于测试)
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            return is_object($role) ? $role->id : $role;
        });
        $this->roles()->sync($roleIds);
        $this->forgetCachedPermissions();
        return $this->fresh(['roles']);
    }

    /**
     * 分配权限 (辅助方法，用于测试)
     */
    public function givePermissionTo($permission): self
    {
        $permissions = is_array($permission) ? $permission : [$permission];
        $permissionIds = collect($permissions)->map(function ($perm) {
            return is_object($perm) ? $perm->id : $perm;
        });
        $this->directPermissions()->syncWithoutDetaching($permissionIds);
        $this->forgetCachedPermissions();
        return $this->fresh(['directPermissions']);
    }

    /**
     * 移除权限 (辅助方法，用于测试)
     */
    public function revokePermissionTo($permission): self
    {
        $permissionId = is_object($permission) ? $permission->id : $permission;
        $this->directPermissions()->detach($permissionId);
        $this->forgetCachedPermissions();
        return $this;
    }

    /**
     * 同步权限 (辅助方法，用于测试)
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($perm) {
            return is_object($perm) ? $perm->id : $perm;
        });
        $this->directPermissions()->sync($permissionIds);
        $this->forgetCachedPermissions();
        return $this->fresh(['directPermissions']);
    }

    /**
     * 检查直接权限 (辅助方法，用于测试)
     */
    public function hasDirectPermission($permission): bool
    {
        $permissionSlug = is_string($permission) ? $permission : $permission->slug;
        return $this->directPermissions->contains('slug', $permissionSlug);
    }

    /**
     * 检查权限 (辅助方法，用于测试)
     */
    public function hasPermissionTo($permission): bool
    {
        return $this->hasPermission($permission);
    }
}