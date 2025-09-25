<?php

namespace Rbac\Actions\Contracts;

/**
 * Action 接口
 * 
 * 定义所有 Action 类的基本契约
 */
interface ActionInterface
{
    /**
     * 执行 Action
     * 
     * @param mixed ...$params 参数列表
     * @return mixed
     */
    public function execute(...$params);
}