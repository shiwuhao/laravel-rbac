<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/19
 * Time: 10:05 PM
 */

namespace Shiwuhao\Rbac\Commands;


use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Shiwuhao\Rbac\Models\Action;
use Shiwuhao\Rbac\Models\Permission;


/**
 * Class GeneratePermissions
 * @package Shiwuhao\Rbac\Commands
 */
class GeneratePermissions extends Command
{

    /**
     * @var string
     */
    protected $signature = 'rbac:generate-permissions
                            {--name= : Filter the routes by name}
                            {--method= : Filter the routes by method}
                            {--except-path= : Do not display the routes matching the given path pattern}
                            {--path= : Only show routes matching the given path pattern}';

    /**
     * @var string
     */
    protected $description = '自动生成权限节点';

    /**
     * @var Router
     */
    protected $router;

    /**
     * GeneratePermissions constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;
    }

    /**
     *
     */
    public function handle()
    {
        $this->createPermissions();
//        $this->createTreePermissions();

        $this->info("Creation Successful");
    }

    protected function createPermissions()
    {
        $routes = $this->getRoutes();

        foreach ($routes as $route) {

            $title = Str::afterLast($route['action'], '\\');
            $condition = ['url' => $route['uri'], 'method' => strtolower($route['method'])];
            Action::updateOrCreate($condition, array_merge($condition, [
                'name' => Str::replace('Controller@', ':', $title),
                'title' => Str::afterLast($route['action'], '\\'),
            ]));
        }
    }

    protected function createTreePermissions()
    {
        $routes = $this->getRoutes();
        $groupRoutes = $this->getGroupRoutes($routes);

        foreach ($groupRoutes as $key => $children) {
            $name = Str::afterLast($key, '\\');
            $condition = ['action' => $key, 'type' => 'menu'];
            $data = array_merge($condition, ['name' => $name, 'title' => $name]);
            $parent = Permission::firstOrCreate($condition, $data);
            foreach ($children as $route) {
                $title = Str::afterLast($route['action'], '\\');
                $url = strtolower($route['method']) . ',' . $route['uri'];
                $condition = ['action' => $route['action'], 'type' => 'action'];

                $data = array_merge($condition, [
                    'pid' => $parent->id,
                    'name' => Str::lower(Str::replace('Controller@', ':', $title)),
                    'url' => $url,
                    'title' => Str::afterLast($route['action'], '\\'),
                ]);
                Permission::firstOrCreate($condition, $data);
            }
        }
    }

    protected function getGroupRoutes($routes)
    {
        return collect($routes)->mapToGroups(function ($item) {
            return [Str::before($item['action'], '@') => $item];
        })->toArray();
    }

    /**
     * 获取路由
     * @return array
     */
    protected function getRoutes()
    {
        return collect($this->router->getRoutes())->map(function ($route) {
            return $this->filterRoute([
                'domain' => $route->domain(),
                'method' => $route->methods()[0],
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => ltrim($route->getActionName(), '\\'),
            ]);
        })->filter()->all();
    }

    /**
     * 过滤路由
     * @param array $route
     * @return array|false
     */
    protected function filterRoute(array $route)
    {
        if ($route['action'] == 'Closure') {
            return false;
        }

        $exceptPaths = array_merge($this->option('except-path') ? explode(',', $this->option('except-path')) : [], config('rbac.except_path'));
        if ($exceptPaths) {
            foreach ($exceptPaths as $path) {
                if (Str::contains($route['uri'], $path)) {
                    return false;
                }
            }
        }

        $paths = array_merge($this->option('path') ? explode(',', $this->option('path')) : [], config('rbac.path'));
        if ($paths) {
            foreach ($paths as $path) {
                if (Str::contains($route['uri'], $path)) {
                    dump($route['uri'] . '|--|' . $path);
                    return $route;
                }
            }
        }

        return false;
    }
}
