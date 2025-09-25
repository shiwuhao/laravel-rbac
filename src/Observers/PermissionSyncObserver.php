<?php

namespace Rbac\Observers;

use Illuminate\Database\Eloquent\Model;
use Rbac\Models\Permission;
use Rbac\Enums\ActionType;
use Illuminate\Support\Str;

/**
 * 权限同步观察者基类
 * 
 * 提供自动权限同步的基础功能，子类只需实现抽象方法即可
 */
abstract class PermissionSyncObserver
{
    /**
     * 模型创建时同步权限
     */
    public function created(Model $model): void
    {
        $this->syncPermissions($model);
    }

    /**
     * 模型更新时同步权限
     */
    public function updated(Model $model): void
    {
        // 只有在关键字段变更时才同步
        if ($this->shouldSyncOnUpdate($model)) {
            $this->syncPermissions($model);
        }
    }

    /**
     * 模型删除时清理权限
     */
    public function deleted(Model $model): void
    {
        $this->cleanupPermissions($model);
    }

    /**
     * 模型恢复时同步权限
     */
    public function restored(Model $model): void
    {
        $this->syncPermissions($model);
    }

    /**
     * 永久删除时清理权限
     */
    public function forceDeleted(Model $model): void
    {
        $this->cleanupPermissions($model);
    }

    /**
     * 同步权限
     */
    protected function syncPermissions(Model $model): void
    {
        $operations = $this->getOperations($model);
        $resource = $this->getResourceType($model);
        $guardName = $this->getGuardName($model);

        foreach ($operations as $operation) {
            $this->createOrUpdatePermission($model, $resource, $operation, $guardName);
        }
    }

    /**
     * 创建或更新权限
     */
    protected function createOrUpdatePermission(Model $model, string $resource, string $operation, string $guardName): void
    {
        $slug = $this->getPermissionSlug($model, $operation);
        
        Permission::updateOrCreate(
            [
                'slug' => $slug,
                'guard_name' => $guardName,
            ],
            [
                'name' => $this->getPermissionName($model, $operation),
                'resource' => $resource,
                'action' => $operation,
                'description' => $this->getPermissionDescription($model, $operation),
                'metadata' => $this->getPermissionMetadata($model, $operation),
            ]
        );
    }

    /**
     * 清理权限
     */
    protected function cleanupPermissions(Model $model): void
    {
        $operations = $this->getOperations($model);
        $guardName = $this->getGuardName($model);

        foreach ($operations as $operation) {
            $slug = $this->getPermissionSlug($model, $operation);
            
            Permission::where('slug', $slug)
                ->where('guard_name', $guardName)
                ->delete();
        }
    }

    /**
     * 判断是否应该在更新时同步权限
     */
    protected function shouldSyncOnUpdate(Model $model): bool
    {
        $keyFields = $this->getKeyFields();
        
        foreach ($keyFields as $field) {
            if ($model->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取权限名称
     */
    protected function getPermissionName(Model $model, string $operation): string
    {
        $modelName = $this->getModelName($model);
        $actionType = ActionType::from($operation);
        
        return $actionType->label() . $modelName;
    }

    /**
     * 获取权限描述
     */
    protected function getPermissionDescription(Model $model, string $operation): string
    {
        $modelName = $this->getModelName($model);
        $actionType = ActionType::from($operation);
        
        return "允许{$actionType->label()}{$modelName}: {$this->getModelIdentifier($model)}";
    }

    /**
     * 获取权限标识符
     */
    protected function getPermissionSlug(Model $model, string $operation): string
    {
        $identifier = $this->getModelSlugIdentifier($model);
        return "{$identifier}.{$operation}";
    }

    /**
     * 获取权限元数据
     */
    protected function getPermissionMetadata(Model $model, string $operation): array
    {
        return [
            'model_class' => get_class($model),
            'model_id' => $model->getKey(),
            'created_by_observer' => static::class,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * 获取模型名称
     */
    protected function getModelName(Model $model): string
    {
        return $this->getModelDisplayName($model) ?: class_basename($model);
    }

    /**
     * 获取模型标识符（用于权限slug）
     */
    protected function getModelSlugIdentifier(Model $model): string
    {
        $slugField = $this->getSlugField();
        
        if ($slugField && isset($model->$slugField)) {
            return $model->$slugField;
        }

        return Str::kebab(class_basename($model)) . '-' . $model->getKey();
    }

    /**
     * 获取模型显示标识符
     */
    protected function getModelIdentifier(Model $model): string
    {
        $nameField = $this->getNameField();
        
        if ($nameField && isset($model->$nameField)) {
            return $model->$nameField;
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    /**
     * 获取守卫名称
     */
    protected function getGuardName(Model $model): string
    {
        return 'web';
    }

    /**
     * 获取关键字段（变更时需要同步权限）
     */
    protected function getKeyFields(): array
    {
        return array_filter([
            $this->getNameField(),
            $this->getSlugField(),
        ]);
    }

    /**
     * 获取名称字段
     */
    protected function getNameField(): ?string
    {
        return 'name';
    }

    /**
     * 获取标识符字段
     */
    protected function getSlugField(): ?string
    {
        return 'slug';
    }

    /**
     * 获取模型显示名称
     */
    protected function getModelDisplayName(Model $model): ?string
    {
        $nameField = $this->getNameField();
        return $nameField ? $model->$nameField : null;
    }

    // 抽象方法 - 子类必须实现

    /**
     * 获取资源类型
     */
    abstract protected function getResourceType(Model $model): string;

    // 可重写方法 - 子类可根据需要自定义

    /**
     * 获取支持的操作类型
     */
    protected function getOperations(Model $model): array
    {
        return [
            ActionType::VIEW->value,
            ActionType::CREATE->value,
            ActionType::UPDATE->value,
            ActionType::DELETE->value,
        ];
    }
}