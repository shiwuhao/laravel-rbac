<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Actions\Permission\CreatePermission;
use Rbac\Models\Permission;

/**
 * 创建权限命令
 * 
 * 用于手动创建单个权限节点
 */
class CreatePermissionCommand extends Command
{
    /**
     * 命令签名
     *
     * @var string
     */
    protected $signature = 'rbac:create-permission
                            {name : 权限名称}
                            {slug : 权限标识符}
                            {--resource= : 资源类型}
                            {--operation= : 操作类型}
                            {--description= : 权限描述}
                            {--guard=web : 守卫名称}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '创建新权限';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug');
        $resourceType = $this->option('resource');
        $operation = $this->option('operation');
        $description = $this->option('description');
        $guard = $this->option('guard');

        try {
            // 检查权限是否已存在
            if (Permission::where('slug', $slug)->where('guard_name', $guard)->exists()) {
                $this->error("权限 '{$slug}' 在守卫 '{$guard}' 下已存在！");
                return Command::FAILURE;
            }

            // 使用 Action 创建权限
            $permission = CreatePermission::handle([
                'name' => $name,
                'slug' => $slug,
                'resource_type' => $resourceType,
                'operation' => $operation,
                'description' => $description,
                'guard_name' => $guard,
            ]);

            $this->info("权限 '{$name}' 创建成功！");
            $this->table(['ID', '名称', '标识符', '资源类型', '操作类型', '守卫'], [
                [
                    $permission->id,
                    $permission->name,
                    $permission->slug,
                    $permission->resource_type ?? '-',
                    $permission->operation ?? '-',
                    $permission->guard_name,
                ]
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("创建权限失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
