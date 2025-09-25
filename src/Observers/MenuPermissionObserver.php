<?php

namespace Rbac\Observers;

use Illuminate\Database\Eloquent\Model;
use Rbac\Enums\ActionType;

/**
 * 菜单权限同步观察者示例
 */
class MenuPermissionObserver extends PermissionSyncObserver
{
    /**
     * 获取资源类型
     */
    protected function getResourceType(Model $model): string
    {
        return 'Menu';
    }

    /**
     * 获取支持的操作类型
     */
    protected function getOperations(Model $model): array
    {
        return [
            ActionType::VIEW->value,
            ActionType::MANAGE->value,
        ];
    }

    /**
     * 获取权限名称
     */
    protected function getPermissionName(Model $model, string $operation): string
    {
        $actionType = ActionType::from($operation);
        $menuName = $model->title ?? $model->name ?? '菜单';
        
        return $actionType->label() . $menuName;
    }

    /**
     * 获取权限描述
     */
    protected function getPermissionDescription(Model $model, string $operation): string
    {
        $actionType = ActionType::from($operation);
        $menuName = $model->title ?? $model->name ?? '菜单';
        
        return "允许{$actionType->description()}: {$menuName}";
    }

    /**
     * 获取名称字段
     */
    protected function getNameField(): ?string
    {
        return 'title';
    }

    /**
     * 获取关键字段
     */
    protected function getKeyFields(): array
    {
        return ['title', 'name', 'slug'];
    }
}