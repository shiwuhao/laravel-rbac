<?php

namespace Rbac\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rbac\Models\Role;
use Rbac\Enums\GuardType;

/**
 * 角色模型工厂
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rbac\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * 模型类名
     */
    protected $model = Role::class;

    /**
     * 定义模型的默认状态
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            '超级管理员', '管理员', '编辑', '作者', '用户', 
            '审核员', '财务', '人事', '客服', '运营'
        ]);
        
        return [
            'name' => $name,
            'slug' => $this->generateSlug($name),
            'description' => $this->faker->sentence(),
            'guard_name' => GuardType::WEB->value,
        ];
    }

    /**
     * 超级管理员状态
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '超级管理员',
            'slug' => 'super-admin',
            'description' => '拥有系统所有权限的超级管理员',
        ]);
    }

    /**
     * 管理员状态
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '管理员',
            'slug' => 'admin',
            'description' => '系统管理员，拥有大部分管理权限',
        ]);
    }

    /**
     * 编辑状态
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '编辑',
            'slug' => 'editor',
            'description' => '内容编辑员，可以管理内容相关功能',
        ]);
    }

    /**
     * 普通用户状态
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '用户',
            'slug' => 'user',
            'description' => '普通用户，拥有基础功能权限',
        ]);
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
     * 生成角色标识符
     */
    private function generateSlug(string $name): string
    {
        $slugMap = [
            '超级管理员' => 'super-admin',
            '管理员' => 'admin',
            '编辑' => 'editor',
            '作者' => 'author',
            '用户' => 'user',
            '审核员' => 'reviewer',
            '财务' => 'finance',
            '人事' => 'hr',
            '客服' => 'support',
            '运营' => 'operator',
        ];

        return $slugMap[$name] ?? \Str::slug($name);
    }
}