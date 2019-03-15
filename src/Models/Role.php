<?php

namespace Shiwuhao\Rbac\Models;


use Illuminate\Database\Eloquent\Model;
use Shiwuhao\Rbac\Contracts\RoleInterface;
use Shiwuhao\Rbac\Traits\RoleTrait;

class Role extends Model implements RoleInterface
{

    use RoleTrait;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('rbac.table.roles_table'));
    }
}
