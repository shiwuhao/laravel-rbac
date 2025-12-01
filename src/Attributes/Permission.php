<?php

namespace Rbac\Attributes;

use Attribute;

/**
 * 权限注解
 * 
 * 用于标记 Action/Controller 方法需要的权限
 * 支持自动生成权限节点和自动权限校验
 * 
 * @example #[Permission('user:create', '创建用户')]
 * @example #[Permission('user:update', '更新用户', description: '更新用户基本信息')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Permission
{
    /**
     * @param string $slug 权限标识符（格式：resource:action）
     * @param string|null $name 权限名称（可选，不填则自动生成）
     * @param string|null $description 权限描述（可选）
     * @param bool $autoCheck 是否自动检查权限（默认 true）
     */
    public function __construct(
        public string $slug,
        public ?string $name = null,
        public ?string $description = null,
        public bool $autoCheck = true
    ) {
    }
}