<?php

namespace Shiwuhao\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Shiwuhao\Rbac\Contracts\PermissionInterface;
use Shiwuhao\Rbac\Traits\PermissionTrait;

/**
 * Class Permission
 * @package App\Models
 */
class Permission extends Model implements PermissionInterface
{
    use PermissionTrait;

    /**
     * @var array
     */
    protected $fillable = [
        'pid', 'name', 'display_name', 'description', 'action',
    ];

    /**
     * Permission constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('rbac.table.permissions'));
    }
}
