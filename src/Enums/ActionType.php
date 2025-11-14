<?php

namespace Rbac\Enums;

/**
 * 操作类型枚举
 * 
 * 定义权限系统中支持的所有操作类型
 * 
 * 核心操作：view, create, update, delete
 * 数据操作：export, import
 * 管理操作：manage, configure
 * 审批操作：approve, reject
 * 实例操作：access, share
 */
enum ActionType: string
{
    // 核心 CRUD 操作
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    
    // 数据操作
    case EXPORT = 'export';
    case IMPORT = 'import';
    
    // 管理操作
    case MANAGE = 'manage';
    case CONFIGURE = 'configure';
    
    // 审批操作
    case APPROVE = 'approve';
    case REJECT = 'reject';
    
    // 实例级操作
    case ACCESS = 'access';    // 访问（如菜单、报表）
    case SHARE = 'share';      // 分享
    case EDIT = 'edit';        // 编辑（update 的别名）

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
            self::ACCESS => '访问',
            self::SHARE => '分享',
            self::EDIT => '编辑',
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
            self::ACCESS => '访问资源（如菜单、报表）',
            self::SHARE => '分享资源给其他用户',
            self::EDIT => '编辑资源（update 的别名）',
        };
    }

    /**
     * 获取所有操作类型选项（包括自定义）
     * 
     * @return array
     */
    public static function options(): array
    {
        $standardOptions = collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();

        $customActions = config('rbac.custom_actions', []);

        return array_merge($standardOptions, $customActions);
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
            self::EDIT,
            self::SHARE,
        ]);
    }

    /**
     * 验证操作类型是否有效（包括自定义操作）
     * 
     * @param string $action 操作类型字符串
     * @return bool
     */
    public static function isValid(string $action): bool
    {
        // 检查是否为标准枚举
        if (self::tryFrom($action) !== null) {
            return true;
        }

        // 检查是否在自定义操作列表中
        $customActions = config('rbac.custom_actions', []);
        return array_key_exists($action, $customActions);
    }

    /**
     * 获取操作类型标签（支持自定义）
     * 
     * @param string $action 操作类型字符串
     * @return string
     */
    public static function getLabel(string $action): string
    {
        // 尝试从枚举获取
        $enum = self::tryFrom($action);
        if ($enum) {
            return $enum->label();
        }

        // 从自定义配置获取
        $customActions = config('rbac.custom_actions', []);
        return $customActions[$action] ?? ucfirst($action);
    }
}