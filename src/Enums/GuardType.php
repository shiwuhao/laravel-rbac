<?php

namespace Rbac\Enums;

/**
 * 守卫类型枚举
 * 
 * 定义不同的认证守卫类型
 */
enum GuardType: string
{
    case WEB = 'web';
    case API = 'api';
    case ADMIN = 'admin';

    /**
     * 获取守卫类型的中文标签
     */
    public function label(): string
    {
        return match($this) {
            self::WEB => 'Web端',
            self::API => 'API接口',
            self::ADMIN => '管理后台',
        };
    }

    /**
     * 获取默认守卫类型
     */
    public static function default(): self
    {
        return self::WEB;
    }

    /**
     * 获取所有守卫类型选项
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}