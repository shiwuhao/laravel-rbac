<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Rbac\Services\RbacService;
use Rbac\Models\Permission;
use Rbac\Enums\ActionType;
use Rbac\Enums\GuardType;

/**
 * 创建权限命令
 */
class CreatePermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:create-permission
                            {name : 权限名称}
                            {slug : 权限标识符}
                            {resource : 资源类型}
                            {action : 操作类型}
                            {--description= : 权限描述}
                            {--guard=web : 守卫名称}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建新权限';

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
        $resource = $this->argument('resource');
        $action = $this->argument('action');
        $description = $this->option('description');
        $guard = $this->option('guard');

        try {
            // 验证操作类型
            if (!$this->isValidAction($action)) {
                $this->error("无效的操作类型: {$action}");
                $this->line("支持的操作类型: " . implode(', ', $this->getValidActions()));
                return Command::FAILURE;
            }

            // 检查权限是否已存在
            if (Permission::where('slug', $slug)->where('guard_name', $guard)->exists()) {
                $this->error("权限 '{$slug}' 在守卫 '{$guard}' 下已存在！");
                return Command::FAILURE;
            }

            // 创建权限
            $permission = $this->rbacService->createPermission(
                $name,
                $slug,
                $resource,
                ActionType::from($action),
                $description,
                GuardType::from($guard)
            );

            $this->info("权限 '{$name}' 创建成功！");
            $this->table(['ID', '名称', '标识符', '资源', '操作', '守卫'], [
                [
                    $permission->id,
                    $permission->name,
                    $permission->slug,
                    $permission->resource,
                    $permission->action->value,
                    $permission->guard_name->value,
                ]
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("创建权限失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 验证操作类型是否有效
     */
    protected function isValidAction(string $action): bool
    {
        return in_array($action, $this->getValidActions());
    }

    /**
     * 获取有效的操作类型
     */
    protected function getValidActions(): array
    {
        return array_column(ActionType::cases(), 'value');
    }
}