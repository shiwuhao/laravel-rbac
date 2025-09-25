<?php

namespace Rbac\Enums;

/**
 * 数据权限范围类型枚举
 * 
 * 定义不同的数据访问范围控制类型
 */
enum DataScopeType: string
{
    case ALL = 'all';                    // 全部数据
    case ORGANIZATION = 'organization';   // 组织数据
    case DEPARTMENT = 'department';       // 部门数据
    case PERSONAL = 'personal';          // 个人数据
    case CUSTOM = 'custom';              // 自定义数据

    /**
     * 获取数据范围类型的中文标签
     */
    public function label(): string
    {
        return match($this) {
            self::ALL => '全部数据',
            self::ORGANIZATION => '组织数据',
            self::DEPARTMENT => '部门数据',
            self::PERSONAL => '个人数据',
            self::CUSTOM => '自定义数据',
        };
    }

    /**
     * 获取数据范围类型的描述
     */
    public function description(): string
    {
        return match($this) {
            self::ALL => '可以访问系统中的所有数据',
            self::ORGANIZATION => '只能访问本组织内的数据',
            self::DEPARTMENT => '只能访问本部门内的数据',
            self::PERSONAL => '只能访问个人创建或负责的数据',
            self::CUSTOM => '根据自定义规则控制数据访问范围',
        };
    }

    /**
     * 获取所有数据范围类型选项
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * 检查是否需要额外配置
     */
    public function requiresConfig(): bool
    {
        return in_array($this, [
            self::ORGANIZATION,
            self::DEPARTMENT,
            self::CUSTOM,
        ]);
    }
}