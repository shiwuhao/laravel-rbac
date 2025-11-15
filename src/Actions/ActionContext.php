<?php

namespace Rbac\Actions;

/**
 * Action 上下文对象
 *
 * 封装 Action 执行所需的所有数据和参数
 */
class ActionContext
{
    /**
     * @param  array  $data  已验证的数据
     * @param  array  $args  额外参数（如 ID 等）
     */
    public function __construct(
        public readonly array $data,
        public readonly array $args = []
    ) {}

    /**
     * 获取数据
     *
     * @param  string|null  $key  键名，为 null 时返回所有数据
     * @param  mixed  $default  默认值
     */
    public function data(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    /**
     * 获取指定位置的参数
     *
     * @param  int  $index  参数索引
     * @param  mixed  $default  默认值
     */
    public function arg(int $index, mixed $default = null): mixed
    {
        return $this->args[$index] ?? $default;
    }

    /**
     * 获取 ID（第一个参数）
     */
    public function id(): mixed
    {
        return $this->arg(0);
    }

    /**
     * 检查数据键是否存在
     *
     * @param  string  $key  键名
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * 获取所有数据
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * 获取过滤后的数据（移除 null 值）
     */
    public function filtered(): array
    {
        return array_filter($this->data, fn ($value) => $value !== null);
    }

    /**
     * 只获取指定的字段
     *
     * @param  array  $keys  字段列表
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }
}
