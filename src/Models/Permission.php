<?php

namespace Shiwuhao\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Shiwuhao\Rbac\Contracts\PermissionInterface;

/**
 * Class Permission
 * @package App\Models
 */
class Permission extends Model implements PermissionInterface
{
    /**
     * @var array
     */
    protected $fillable = [
        'pid', 'name', 'label'
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
     * @return MorphTo
     */
    public function permissible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 获取拥有此节点的角色
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.model.permission'),
            config('rbac.table.role_permission'),
            config('rbac.foreign_key.permission'),
            config('rbac.foreign_key.role')
        );
    }
}
