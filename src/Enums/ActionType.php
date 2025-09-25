<?php

namespace Rbac\Enums;

/**
 * 操作类型枚举
 * 
 * 定义权限系统中支持的所有操作类型
 */
enum ActionType: string
{
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case EXPORT = 'export';
    case IMPORT = 'import';
    case MANAGE = 'manage';
    case CONFIGURE = 'configure';
    case APPROVE = 'approve';
    case REJECT = 'reject';

    /**
     * 获取操作类型的中文标签
     */
    public function label(): string
    {
        return match($this) {
            self::VIEW => '查看',
            self::CREATE => '创建',
            self::UPDATE => '更新',
            self::DELETE => '删除',
            self::EXPORT => '导出',
            self::IMPORT => '导入',
            self::MANAGE => '管理',
            self::CONFIGURE => '配置',
            self::APPROVE => '审批',
            self::REJECT => '拒绝',
        };
    }

    /**
     * 获取操作类型的描述
     */
    public function description(): string
    {
        return match($this) {
            self::VIEW => '查看资源内容',
            self::CREATE => '创建新的资源',
            self::UPDATE => '更新现有资源',
            self::DELETE => '删除资源',
            self::EXPORT => '导出资源数据',
            self::IMPORT => '导入资源数据',
            self::MANAGE => '管理资源',
            self::CONFIGURE => '配置资源设置',
            self::APPROVE => '审批通过',
            self::REJECT => '审批拒绝',
        };
    }

    /**
     * 获取所有操作类型选项
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * 检查是否为写操作
     */
    public function isWriteOperation(): bool
    {
        return in_array($this, [
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::IMPORT,
            self::APPROVE,
            self::REJECT,
        ]);
    }
}