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
 * @property string $resource 资源标识(数据库字段)
 * @property string $action 操作类型(数据库字段)
 * @property string|null $description
 * @property GuardType $guard_name
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read string $resource_type 资源类型(访问器别名)
 * @property-read string $operation 操作(访问器别名)
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
        'resource_type',
        'resource_id',
        'operation',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 访问器: resource_type (映射到 resource 字段)
     */
    public function getResourceTypeAttribute(): ?string
    {
        return $this->attributes['resource'] ?? null;
    }

    /**
     * 修改器: resource_type (映射到 resource 字段)
     */
    public function setResourceTypeAttribute(?string $value): void
    {
        $this->attributes['resource'] = $value;
    }

    /**
     * 访问器: operation (映射到 action 字段)
     */
    public function getOperationAttribute(): ?string
    {
        return $this->attributes['action'] ?? null;
    }

    /**
     * 修改器: operation (映射到 action 字段)
     */
    public function setOperationAttribute(?string $value): void
    {
        $this->attributes['action'] = $value;
    }

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
    public function scopeByResourceType(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource', $resourceType);
    }

    /**
     * 根据资源实例查询
     */
    public function scopeByResourceInstance(Builder $query, string $resourceType, int $resourceId): Builder
    {
        return $query->where('resource', $resourceType)
                    ->where('resource_id', $resourceId);
    }

    /**
     * 查询通用权限（不针对特定实例）
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('resource_id');
    }

    /**
     * 查询实例权限（针对特定实例）
     */
    public function scopeInstance(Builder $query): Builder
    {
        return $query->whereNotNull('resource_id');
    }

    /**
     * 根据操作类型查询
     */
    public function scopeByOperation(Builder $query, string $operation): Builder
    {
        return $query->where('action', $operation);
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
    public function scopeByResourceTypeOperation(Builder $query, string $resourceType, string $operation): Builder
    {
        return $query->where('resource', $resourceType)
                    ->where('action', $operation);
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
    public static function generateSlug(string $resourceType, string $operation, ?int $resourceId = null): string
    {
        $slug = strtolower($resourceType) . '.' . $operation;
        if ($resourceId !== null) {
            $slug .= '.' . $resourceId;
        }
        return $slug;
    }

    /**
     * 生成权限名称
     */
    public static function generateName(string $resourceType, string $operation, ?int $resourceId = null): string
    {
        $operationLabels = [
            'view' => '查看',
            'create' => '创建',
            'update' => '编辑',
            'delete' => '删除',
            'export' => '导出',
            'import' => '导入',
        ];

        $operationLabel = $operationLabels[$operation] ?? $operation;
        $name = $operationLabel . $resourceType;

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
        return !is_null($this->resource_id);
    }

    /**
     * 检查是否为通用权限
     */
    public function isGeneralPermission(): bool
    {
        return is_null($this->resource_id);
    }

    /**
     * 获取权限完整描述
     */
    public function getFullDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $operationLabels = [
            'view' => '查看',
            'create' => '创建',
            'update' => '编辑',
            'delete' => '删除',
            'export' => '导出',
            'import' => '导入',
        ];

        $operationLabel = $operationLabels[$this->action] ?? $this->action;
        $desc = $operationLabel . ' - ' . $this->resource;

        if ($this->resource_id) {
            $desc .= "(#{$this->resource_id})";
        }

        return $desc;
    }

    /**
     * 创建工厂
     */
    protected static function newFactory()
    {
        return \Rbac\Database\Factories\PermissionFactory::new();
    }
}