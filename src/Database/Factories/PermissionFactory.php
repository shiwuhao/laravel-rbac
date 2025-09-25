<?php

namespace Rbac\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rbac\Models\Permission;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;

/**
 * 权限模型工厂
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rbac\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * 模型类名
     */
    protected $model = Permission::class;

    /**
     * 定义模型的默认状态
     */
    public function definition(): array
    {
        $resource = $this->faker->randomElement([
            'User', 'Role', 'Permission', 'Post', 'Comment', 
            'Category', 'Tag', 'File', 'Setting', 'Log'
        ]);
        
        $action = $this->faker->randomElement(ActionType::cases());
        
        return [
            'name' => $action->label() . $resource,
            'slug' => strtolower($resource) . '.' . $action->value,
            'resource' => $resource,
            'action' => $action->value,
            'description' => "允许{$action->description()}: {$resource}",
            'guard_name' => GuardType::WEB->value,
            'metadata' => null,
        ];
    }

    /**
     * 用户管理权限
     */
    public function userManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource' => 'User',
        ]);
    }

    /**
     * 角色管理权限
     */
    public function roleManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource' => 'Role',
        ]);
    }

    /**
     * 内容管理权限
     */
    public function contentManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource' => $this->faker->randomElement(['Post', 'Comment', 'Category']),
        ]);
    }

    /**
     * 系统管理权限
     */
    public function systemManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'resource' => $this->faker->randomElement(['Setting', 'Log', 'File']),
        ]);
    }

    /**
     * 查看操作
     */
    public function viewAction(): static
    {
        return $this->state(function (array $attributes) {
            $action = ActionType::VIEW;
            return [
                'action' => $action->value,
                'name' => $action->label() . $attributes['resource'],
                'slug' => strtolower($attributes['resource']) . '.' . $action->value,
                'description' => "允许{$action->description()}: {$attributes['resource']}",
            ];
        });
    }

    /**
     * 创建操作
     */
    public function createAction(): static
    {
        return $this->state(function (array $attributes) {
            $action = ActionType::CREATE;
            return [
                'action' => $action->value,
                'name' => $action->label() . $attributes['resource'],
                'slug' => strtolower($attributes['resource']) . '.' . $action->value,
                'description' => "允许{$action->description()}: {$attributes['resource']}",
            ];
        });
    }

    /**
     * 更新操作
     */
    public function updateAction(): static
    {
        return $this->state(function (array $attributes) {
            $action = ActionType::UPDATE;
            return [
                'action' => $action->value,
                'name' => $action->label() . $attributes['resource'],
                'slug' => strtolower($attributes['resource']) . '.' . $action->value,
                'description' => "允许{$action->description()}: {$attributes['resource']}",
            ];
        });
    }

    /**
     * 删除操作
     */
    public function deleteAction(): static
    {
        return $this->state(function (array $attributes) {
            $action = ActionType::DELETE;
            return [
                'action' => $action->value,
                'name' => $action->label() . $attributes['resource'],
                'slug' => strtolower($attributes['resource']) . '.' . $action->value,
                'description' => "允许{$action->description()}: {$attributes['resource']}",
            ];
        });
    }

    /**
     * API 守卫状态
     */
    public function apiGuard(): static
    {
        return $this->state(fn (array $attributes) => [
            'guard_name' => GuardType::API->value,
        ]);
    }

    /**
     * 带有元数据
     */
    public function withMetadata(array $metadata = []): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge([
                'created_by_factory' => true,
                'category' => $this->faker->randomElement(['system', 'content', 'user']),
            ], $metadata),
        ]);
    }
}