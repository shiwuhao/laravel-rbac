<?php

namespace Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;
use Rbac\Contracts\PermissionContract;

/**
 * 权限模型
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $resource
 * @property ActionType $action
 * @property string|null $description
 * @property GuardType $guard_name
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Permission extends Model implements PermissionContract
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'resource',
        'action',
        'description',
        'guard_name',
        'metadata',
    ];

    protected $casts = [
        'action' => ActionType::class,
        'guard_name' => GuardType::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 获取表名
     */
    public function getTable(): string
    {
        return config('rbac.tables.permissions', parent::getTable());
    }

    /**
     * 角色关联
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.role'),
            config('rbac.tables.role_permission'),
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * 用户关联（直接权限）
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('rbac.tables.user_permission'),
            'permission_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * 数据范围关联
     */
    public function dataScopes(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rbac.models.data_scope'),
            config('rbac.tables.permission_data_scope'),
            'permission_id',
            'data_scope_id'
        )->withPivot('constraint')->withTimestamps();
    }

    /**
     * 根据资源类型查询
     */
    public function scopeByResource(Builder $query, string $resource): Builder
    {
        return $query->where('resource', $resource);
    }

    /**
     * 根据操作类型查询
     */
    public function scopeByAction(Builder $query, string|ActionType $action): Builder
    {
        $actionValue = $action instanceof ActionType ? $action->value : $action;
        return $query->where('action', $actionValue);
    }

    /**
     * 根据守卫查询
     */
    public function scopeByGuard(Builder $query, string|GuardType $guard): Builder
    {
        $guardValue = $guard instanceof GuardType ? $guard->value : $guard;
        return $query->where('guard_name', $guardValue);
    }

    /**
     * 根据标识符查询
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * 根据资源和操作查询
     */
    public function scopeByResourceAction(Builder $query, string $resource, string|ActionType $action): Builder
    {
        $actionValue = $action instanceof ActionType ? $action->value : $action;
        return $query->where('resource', $resource)
                    ->where('action', $actionValue);
    }

    /**
     * 查询写操作权限
     */
    public function scopeWriteOperations(Builder $query): Builder
    {
        return $query->whereIn('action', [
            ActionType::CREATE->value,
            ActionType::UPDATE->value,
            ActionType::DELETE->value,
            ActionType::IMPORT->value,
        ]);
    }

    /**
     * 查询读操作权限
     */
    public function scopeReadOperations(Builder $query): Builder
    {
        return $query->whereIn('action', [
            ActionType::VIEW->value,
            ActionType::EXPORT->value,
        ]);
    }

    /**
     * 生成权限标识符
     */
    public static function generateSlug(string $resource, string|ActionType $action): string
    {
        $actionValue = $action instanceof ActionType ? $action->value : $action;
        return strtolower($resource) . '.' . $actionValue;
    }

    /**
     * 生成权限名称
     */
    public static function generateName(string $resource, string|ActionType $action): string
    {
        $actionObj = is_string($action) ? ActionType::from($action) : $action;
        return $actionObj->label() . $resource;
    }

    /**
     * 检查是否为写操作
     */
    public function isWriteOperation(): bool
    {
        return $this->action->isWriteOperation();
    }

    /**
     * 获取权限完整描述
     */
    public function getFullDescriptionAttribute(): string
    {
        return $this->description ?: $this->action->description() . ' - ' . $this->resource;
    }

    /**
     * 创建工厂
     */
    protected static function newFactory()
    {
        return \Rbac\Database\Factories\PermissionFactory::new();
    }
}