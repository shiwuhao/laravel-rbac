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

    const TYPE_ACTION = 'action';
    const TYPE_MENU = 'menu';

    /**
     * @var array
     */
    protected $fillable = [
        'pid', 'type', 'name', 'title', 'icon', 'url', 'method'
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

    /**
     * 获取拥有此权限的模型
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function permissible(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
