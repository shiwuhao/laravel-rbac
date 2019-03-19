<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/19
 * Time: 10:05 PM
 */

namespace Shiwuhao\Rbac\Commands;


use Illuminate\Console\Command;
use Shiwuhao\Rbac\Contracts\PermissionInterface;
use Shiwuhao\Rbac\Contracts\RoleInterface;

/**
 * Class CreatePermission
 * @package Shiwuhao\Rbac\Commands
 */
class CreatePermission extends Command
{

    /**
     * @var string
     */
    protected $signature = 'rbac:create-permission
                            {name : 权限唯一标识}
                            {display_name : 权限显示名称}
                            {description? : 权限描述}
                            {action? : Controller action}
                            {pid? : 父级节点ID}
                            ';

    /**
     * @var string
     */
    protected $description = '创建权限';

    /**
     * handle
     */
    public function handle()
    {
        $permission = app(PermissionInterface::class)::firstOrCreate(
            ['name' => $this->argument('name')],
            [
                'display_name' => $this->argument('display_name'),
                'description' => $this->argument('description') ?? '',
                'action' => $this->argument('action') ?? '1',
                'pid' => $this->argument('pid') ?? 0,
            ]
        );

        $this->info("Permission {$permission->name} created");
    }
}
