<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/19
 * Time: 10:05 PM
 */

namespace Shiwuhao\Rbac\Commands;


use Illuminate\Console\Command;
use Shiwuhao\Rbac\Exceptions\InvalidArgumentException;
use Shiwuhao\Rbac\Models\Permission;

/**
 * Class AutoGeneratePermissions
 * @package Shiwuhao\Rbac\Commands
 */
class AutoGeneratePermissions extends Command
{

    /**
     * @var string
     */
    protected $signature = 'rbac:auto-generate-permissions';

    /**
     * @var string
     */
    protected $description = '自动生成权限节点';

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public function handle()
    {
        $controllers = config('rbac.needGeneratePermission');
        if (empty($controllers)) {
            throw new InvalidArgumentException('config needGeneratePermission is empty');
        }
        foreach ($controllers as $controller => $label) {
            $this->generatePermissionByController($controller, $label);
            $this->info("Permission {$controller} Creation Successful");
        }

        $this->info("Creation Successful");
    }

    /**
     * 根据controller 生成 节点
     * @param $controller
     * @param $label
     * @throws \ReflectionException
     */
    protected function generatePermissionByController($controller, $label)
    {
        $class = new \ReflectionClass($controller);
        $className = $class->getShortName();

        $namePrefix = strtolower(substr($className, 0, strpos($className, 'Controller'))) . ':'; // name标识前缀
        $labelPrefix = mb_substr($label, 0, -2);

        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $actionLabel = config('rbac.resourceAbilityMapLabel');
        $actionReplace = config('rbac.resourceAbilityMap');

        $condition = ['action' => $controller];
        $permission = array_merge($condition, [
            'name' => $className,
            'display_name' => $label
        ]);
        $parent = Permission::updateOrCreate($condition, $permission);

        foreach ($methods as $key => $method) {
            if ($controller != $method->class) {
                unset($methods[$key]);
                continue;
            }

            $doc = $method->getDocComment();
            $methodName = $method->getName();
            preg_match_all('/@node(.*?)\n/', $doc, $methodMatches);
            $nodeName = !empty($methodMatches[1][0]) ? trim($methodMatches[1][0]) : (!empty($actionLabel[$methodName]) ? $labelPrefix . $actionLabel[$methodName] : $methodName);
            $condition = ['action' => $controller . '@' . $methodName];
            $methodName = $actionReplace[$methodName] ?? $methodName;
            $permission = array_merge($condition, [
                'pid' => $parent->id,
                'name' => $namePrefix . $methodName,
                'display_name' => $nodeName
            ]);

            Permission::updateOrCreate($condition, $permission);

        }
    }
}
