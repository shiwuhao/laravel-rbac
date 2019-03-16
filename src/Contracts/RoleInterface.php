<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 4:58 PM
 */

namespace Shiwuhao\Rbac\Contracts;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Interface Role
 * @package Shiwuhao\Rbac\Contracts
 */
interface RoleInterface
{
    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany;

    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * @param $permissions
     */
    public function attachPermissions($permissions): void;

    /**
     * @param $permissions
     */
    public function detachPermissions($permissions): void;

    /**
     * @param $permissions
     */
    public function syncPermissions($permissions): void;
}
