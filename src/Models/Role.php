<?php

namespace Shiwuhao\Rbac\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Shiwuhao\Rbac\Contracts\RoleInterface;

/**
 * Class Role
 * @package Shiwuhao\Rbac\Models
 */
class Role extends Model implements RoleInterface
{

    /**
     * Role constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('rbac.table.roles'));
    }


    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.user'),
            config('rbac.table.role_user'),
            config('rbac.foreign_key.role'),
            config('rbac.foreign_key.user')
        );
    }

    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.permission'),
            config('rbac.table.role_permission'),
            config('rbac.foreign_key.role'),
            config('rbac.foreign_key.permission')
        );
    }
}
