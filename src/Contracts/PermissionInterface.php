<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 4:56 PM
 */

namespace Shiwuhao\Rbac\Contracts;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Interface Permission
 * @package Shiwuhao\Rbac\Contracts
 */
interface PermissionInterface
{
    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany;

}
