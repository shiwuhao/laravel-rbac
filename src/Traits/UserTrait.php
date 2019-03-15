<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Shiwuhao\Traits\BaseTrait;

trait UserTrait
{
    use BaseTrait;

    /**
     * 获取用户拥有的角色
     * 用户 角色 多对多关联
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('rbac.models.role'), 'role_user', 'role_id', 'user_id');
    }

    public function hasRoles($names): bool
    {
        $names = is_array($names) ? $names : explode('|', trim($names, '|'));
        $roleNames = $this->roles()->get()->pluck('name');

        foreach ($names as $name) {
            return $roleNames->contains($name);
        }

        return false;
    }
}
