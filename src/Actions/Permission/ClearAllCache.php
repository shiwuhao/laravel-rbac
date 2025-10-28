<?php

namespace Rbac\Actions\Permission;

use Illuminate\Support\Facades\Cache;
use Rbac\Actions\BaseAction;
use Rbac\Attributes\Permission as PermissionAttribute;
use Rbac\Attributes\PermissionGroup;

#[PermissionGroup('permission:*', '权限管理')]
#[PermissionAttribute('permission:clear-cache', '清除权限缓存')]
class ClearAllCache extends BaseAction
{
    /**
     * 清除所有权限缓存
     *
     * @return array{cleared: bool}
     */
    protected function execute(): array
    {
        $cacheKey = config('rbac.cache.key');
        $cacheDriver = config('cache.default');
        
        if ($cacheDriver === 'redis' || $cacheDriver === 'memcached') {
            // 对于 Redis 和 Memcached，尝试清除所有相关键
            try {
                $redis = Cache::getStore()->getRedis();
                $pattern = $cacheKey . '.*';
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                }
            } catch (\Exception $e) {
                // 如果无法直接访问 Redis，回退到标签清除
                Cache::tags(['rbac'])->flush();
            }
        } else {
            // 对于其他缓存驱动，使用标签清除
            Cache::tags(['rbac'])->flush();
        }

        return ['cleared' => true];
    }
}