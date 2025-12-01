<?php

namespace Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Rbac\Enums\DataScopeType;
use Rbac\Contracts\DataScopeContract;

/**
 * 数据范围模型
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property DataScopeType $type
 * @property array|null $config
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DataScope extends Model implements DataScopeContract
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'config',
        'description',
    ];

    protected $casts = [
        'type' => DataScopeType::class,
        'config' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // 自动生成 slug（如果未提供）
        static::creating(function ($dataScope) {
            if (empty($dataScope->slug)) {
                // 使用 type 值作为 slug，加上随机后缀避免冲突
                $baseSlug = $dataScope->type->value ?? 'scope';
                $dataScope->slug = $baseSlug . '-' . \Illuminate\Support\Str::random(8);
            }
        });
    }

    /**
     * 获取表名
     */
    public function getTable(): string
    {
        return config('rbac.tables.data_scopes', parent::getTable());
    }

    /**
     * 用户关联
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('rbac.tables.user_data_scope'),
            'data_scope_id',
            'user_id'
        )->withPivot('constraint')->withTimestamps();
    }

    /**
     * 应用数据范围过滤
     */
    public function applyScope(Builder $query, $user): Builder
    {
        return match($this->type) {
            DataScopeType::ALL => $query,
            DataScopeType::PERSONAL => $this->applyPersonalScope($query, $user),
            DataScopeType::DEPARTMENT => $this->applyDepartmentScope($query, $user),
            DataScopeType::ORGANIZATION => $this->applyOrganizationScope($query, $user),
            DataScopeType::CUSTOM => $this->applyCustomScope($query, $user),
        };
    }

    /**
     * 应用个人数据范围
     */
    protected function applyPersonalScope(Builder $query, $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * 应用部门数据范围
     */
    protected function applyDepartmentScope(Builder $query, $user): Builder
    {
        if (!method_exists($user, 'departmentIds')) {
            return $query->whereRaw('1 = 0'); // 无部门信息返回空结果
        }

        $departmentIds = $user->departmentIds();

        if (empty($departmentIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('department_id', $departmentIds);
    }

    /**
     * 应用组织数据范围
     */
    protected function applyOrganizationScope(Builder $query, $user): Builder
    {
        if (!isset($user->organization_id)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('organization_id', $user->organization_id);
    }

    /**
     * 应用自定义数据范围
     */
    protected function applyCustomScope(Builder $query, $user): Builder
    {
        if (empty($this->config)) {
            return $query;
        }

        foreach ($this->config as $field => $condition) {
            if (is_callable($condition)) {
                $query = $condition($query, $user);
            } elseif (is_array($condition)) {
                $query = $query->whereIn($field, $condition);
            } else {
                $query = $query->where($field, $condition);
            }
        }

        return $query;
    }

    /**
     * 检查用户是否可以访问模型
     */
    public function canAccess(Model $model, $user): bool
    {
        return match($this->type) {
            DataScopeType::ALL => true,
            DataScopeType::PERSONAL => $this->canAccessPersonal($model, $user),
            DataScopeType::DEPARTMENT => $this->canAccessDepartment($model, $user),
            DataScopeType::ORGANIZATION => $this->canAccessOrganization($model, $user),
            DataScopeType::CUSTOM => $this->canAccessCustom($model, $user),
        };
    }

    /**
     * 检查个人数据访问权限
     */
    protected function canAccessPersonal(Model $model, $user): bool
    {
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    /**
     * 检查部门数据访问权限
     */
    protected function canAccessDepartment(Model $model, $user): bool
    {
        if (!method_exists($user, 'departmentIds') || !isset($model->department_id)) {
            return false;
        }

        return in_array($model->department_id, $user->departmentIds());
    }

    /**
     * 检查组织数据访问权限
     */
    protected function canAccessOrganization(Model $model, $user): bool
    {
        return isset($model->organization_id) &&
               isset($user->organization_id) &&
               $model->organization_id === $user->organization_id;
    }

    /**
     * 检查自定义数据访问权限
     */
    protected function canAccessCustom(Model $model, $user): bool
    {
        if (empty($this->config)) {
            return true;
        }

        foreach ($this->config as $field => $condition) {
            if (is_callable($condition)) {
                if (!$condition($model, $user)) {
                    return false;
                }
            } elseif (is_array($condition)) {
                if (!in_array($model->$field, $condition)) {
                    return false;
                }
            } else {
                if ($model->$field !== $condition) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 根据类型查询
     */
    public function scopeByType(Builder $query, string|DataScopeType $type): Builder
    {
        $typeValue = $type instanceof DataScopeType ? $type->value : $type;
        return $query->where('type', $typeValue);
    }

    /**
     * 根据 slug 查询
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * 创建工厂
     */
    protected static function newFactory()
    {
        return \Rbac\Database\Factories\DataScopeFactory::new();
    }
}