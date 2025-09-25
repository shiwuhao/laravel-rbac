<?php

namespace Rbac\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rbac\Models\DataScope;
use Rbac\Enums\DataScopeType;

/**
 * 数据范围模型工厂
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rbac\Models\DataScope>
 */
class DataScopeFactory extends Factory
{
    /**
     * 模型类名
     */
    protected $model = DataScope::class;

    /**
     * 定义模型的默认状态
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(DataScopeType::cases());
        
        return [
            'name' => $this->generateName($type),
            'type' => $type->value,
            'config' => $this->generateConfig($type),
            'description' => $type->description(),
        ];
    }

    /**
     * 全部数据范围
     */
    public function allData(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '全部数据',
            'type' => DataScopeType::ALL->value,
            'config' => null,
            'description' => '可以访问系统中的所有数据',
        ]);
    }

    /**
     * 组织数据范围
     */
    public function organizationData(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '本组织数据',
            'type' => DataScopeType::ORGANIZATION->value,
            'config' => [
                'organization_field' => 'organization_id',
                'include_sub_organizations' => true,
            ],
            'description' => '只能访问本组织及下属组织的数据',
        ]);
    }

    /**
     * 部门数据范围
     */
    public function departmentData(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '本部门数据',
            'type' => DataScopeType::DEPARTMENT->value,
            'config' => [
                'department_field' => 'department_id',
                'include_sub_departments' => false,
            ],
            'description' => '只能访问本部门的数据',
        ]);
    }

    /**
     * 个人数据范围
     */
    public function personalData(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '个人数据',
            'type' => DataScopeType::PERSONAL->value,
            'config' => [
                'user_field' => 'user_id',
                'creator_field' => 'created_by',
            ],
            'description' => '只能访问个人创建或负责的数据',
        ]);
    }

    /**
     * 自定义数据范围
     */
    public function customData(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '自定义数据范围',
            'type' => DataScopeType::CUSTOM->value,
            'config' => [
                'rules' => [
                    [
                        'field' => 'status',
                        'operator' => 'in',
                        'value' => ['active', 'pending'],
                    ],
                    [
                        'field' => 'created_at',
                        'operator' => '>=',
                        'value' => '2024-01-01',
                    ],
                ],
            ],
            'description' => '根据自定义规则控制数据访问范围',
        ]);
    }

    /**
     * 生成名称
     */
    private function generateName(DataScopeType $type): string
    {
        return match($type) {
            DataScopeType::ALL => '全部数据',
            DataScopeType::ORGANIZATION => $this->faker->randomElement([
                '本组织数据', '集团数据', '公司数据'
            ]),
            DataScopeType::DEPARTMENT => $this->faker->randomElement([
                '本部门数据', '部门数据', '科室数据'
            ]),
            DataScopeType::PERSONAL => $this->faker->randomElement([
                '个人数据', '我的数据', '私有数据'
            ]),
            DataScopeType::CUSTOM => $this->faker->randomElement([
                '自定义范围', '特殊权限', '临时授权'
            ]),
        };
    }

    /**
     * 生成配置
     */
    private function generateConfig(DataScopeType $type): ?array
    {
        return match($type) {
            DataScopeType::ALL => null,
            DataScopeType::ORGANIZATION => [
                'organization_field' => 'organization_id',
                'include_sub_organizations' => $this->faker->boolean(),
            ],
            DataScopeType::DEPARTMENT => [
                'department_field' => 'department_id',
                'include_sub_departments' => $this->faker->boolean(),
            ],
            DataScopeType::PERSONAL => [
                'user_field' => 'user_id',
                'creator_field' => 'created_by',
            ],
            DataScopeType::CUSTOM => [
                'rules' => [
                    [
                        'field' => $this->faker->randomElement(['status', 'type', 'category']),
                        'operator' => $this->faker->randomElement(['=', '!=', 'in', 'not_in']),
                        'value' => $this->faker->randomElements(['active', 'inactive', 'pending'], 2),
                    ],
                ],
            ],
        };
    }
}