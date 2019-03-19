<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/19
 * Time: 10:05 PM
 */

namespace Shiwuhao\Rbac\Commands;


use Illuminate\Console\Command;
use Shiwuhao\Rbac\Contracts\RoleInterface;

/**
 * Class CreateRole
 * @package Shiwuhao\Rbac\Commands
 */
class CreateRole extends Command
{

    /**
     * @var string
     */
    protected $signature = 'rbac:create-role
                            {name : 角色唯一标识}
                            {display_name : 角色显示名称}';

    /**
     * @var string
     */
    protected $description = '创建角色';

    /**
     * handle
     */
    public function handle()
    {
        $role = app(RoleInterface::class)::firstOrCreate(
            ['name' => $this->argument('name')],
            ['display_name' => $this->argument('display_name')]
        );

        $this->info("Role {$role->name} created");
    }
}
