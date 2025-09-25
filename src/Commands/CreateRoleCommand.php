<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Services\RbacService;
use Rbac\Models\Role;
use Rbac\Enums\GuardType;

/**
 * 创建角色命令
 */
class CreateRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:create-role 
                            {name : 角色名称}
                            {slug : 角色标识符}
                            {--description= : 角色描述}
                            {--guard=web : 守卫名称}
                            {--permissions= : 权限列表(逗号分隔)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建新角色';

    protected RbacService $rbacService;

    public function __construct(RbacService $rbacService)
    {
        parent::__construct();
        $this->rbacService = $rbacService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug');
        $description = $this->option('description');
        $guard = $this->option('guard');
        $permissions = $this->option('permissions');

        try {
            // 检查角色是否已存在
            if (Role::where('slug', $slug)->where('guard_name', $guard)->exists()) {
                $this->error("角色 '{$slug}' 在守卫 '{$guard}' 下已存在！");
                return Command::FAILURE;
            }

            // 创建角色
            $role = $this->rbacService->createRole($name, $slug, $description, $guard);

            $this->info("角色 '{$name}' 创建成功！");
            $this->table(['ID', '名称', '标识符', '描述', '守卫'], [
                [$role->id, $role->name, $role->slug, $role->description, $role->guard_name]
            ]);

            // 分配权限
            if ($permissions) {
                $this->assignPermissions($role, $permissions);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("创建角色失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 分配权限给角色
     */
    protected function assignPermissions(Role $role, string $permissions): void
    {
        $permissionSlugs = array_map('trim', explode(',', $permissions));
        $assignedCount = 0;

        foreach ($permissionSlugs as $permissionSlug) {
            $permission = \Rbac\Models\Permission::where('slug', $permissionSlug)
                ->where('guard_name', $role->guard_name)
                ->first();

            if ($permission) {
                $this->rbacService->assignPermissionToRole($role, $permission);
                $assignedCount++;
            } else {
                $this->warn("权限 '{$permissionSlug}' 不存在，跳过分配");
            }
        }

        if ($assignedCount > 0) {
            $this->info("成功为角色分配了 {$assignedCount} 个权限");
        }
    }
}