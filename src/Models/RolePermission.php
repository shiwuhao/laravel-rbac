<?php

namespace Shiwuhao\Rbac\Models;


use Illuminate\Database\Eloquent\Model;
use Shiwuhao\Rbac\Contracts\RoleInterface;
use Shiwuhao\Rbac\Traits\RoleTrait;

/**
 * Class Role
 * @package Shiwuhao\Rbac\Models
 */
class RolePermission extends Model
{
    /**
     * Role constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('rbac.table.permission_role'));
    }
}
