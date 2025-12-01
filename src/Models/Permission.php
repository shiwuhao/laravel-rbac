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
 * @property string $resource 资源标识
 * @property string $action 操作类型
 * @property string|null $resource_type 资源模型类型（多态）
 * @property int|null $resource_id 资源实例ID（多态）
 * @property string|null $description
 * @property string $guard_name
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
        'description',
        'resource',
        'action',
        'guard_name',
        'metadata',
        'resource_type',  // 多态资源类型
        'resource_id',    // 多态资源ID
    ];

    protected $casts = [
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
     * 多态关联：资源实例（报表、菜单等）
     */
    public function resourceInstance()
    {
        return $this->morphTo('resource', 'resource_type', 'resource_id');
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
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
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
     * 根据资源类型和操作查询
     */
    public function scopeByResourceAction(Builder $query, string $resource, string $action): Builder
    {
        return $query->where('resource', $resource)
                    ->where('action', $action);
    }

    /**
     * 查询写操作权限
     */
    public function scopeWriteOperations(Builder $query): Builder
    {
        return $query->whereIn('action', [
            'create',
            'update',
            'delete',
            'import',
        ]);
    }

    /**
     * 查询读操作权限
     */
    public function scopeReadOperations(Builder $query): Builder
    {
        return $query->whereIn('action', [
            'view',
            'export',
        ]);
    }

    /**
     * 生成权限标识符
     */
    public static function generateSlug(string $resource, string $action, ?int $resourceId = null): string
    {
        $slug = strtolower($resource) . '.' . $action;
        if ($resourceId !== null) {
            $slug .= '.' . $resourceId;
        }
        return $slug;
    }

    /**
     * 生成权限名称
     */
    public static function generateName(string $resource, string $action, ?int $resourceId = null): string
    {
        $actionLabels = [
            'view' => '查看',
            'create' => '创建',
            'update' => '编辑',
            'delete' => '删除',
            'export' => '导出',
            'import' => '导入',
        ];

        $actionLabel = $actionLabels[$action] ?? $action;
        $name = $actionLabel . $resource;

        if ($resourceId !== null) {
            $name .= "(#{$resourceId})";
        }

        return $name;
    }

    /**
     * 检查是否为写操作
     */
    public function isWriteOperation(): bool
    {
        return in_array($this->action, ['create', 'update', 'delete', 'import']);
    }

    /**
     * 检查是否为实例权限
     */
    public function isInstancePermission(): bool
    {
        return !empty($this->resource_type) && !empty($this->resource_id);
    }

    /**
     * 检查是否为通用权限
     */
    public function isGeneralPermission(): bool
    {
        return empty($this->resource_type) && empty($this->resource_id);
    }

    /**
     * 获取权限完整描述
     */
    public function getFullDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $actionLabels = [
            'view' => '查看',
            'create' => '创建',
            'update' => '编辑',
            'delete' => '删除',
            'export' => '导出',
            'import' => '导入',
        ];

        $actionLabel = $actionLabels[$this->action] ?? $this->action;
        return $actionLabel . ' - ' . $this->resource;
    }

    /**
     * 创建工厂
     */
    protected static function newFactory()
    {
        return \Rbac\Database\Factories\PermissionFactory::new();
    }
}