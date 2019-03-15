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
    public function permissions(): BelongsToMany;

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany;

    /**
     *
     */
    public function attachPermissions(): void;

    /**
     *
     */
    public function detachPermissions(): void;
}
