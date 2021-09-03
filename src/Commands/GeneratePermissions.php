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
        $this->createTreePermissions();

        $this->info("Creation Successful");
    }

    protected function createPermissions()
    {
        $routes = $this->getRoutes();
        foreach ($routes as $route) {
            $condition = ['method' => strtolower($route['method']), 'url' => $route['uri']];
            $data = array_merge($condition, ['name' => join(',', $condition)]);
            Permission::updateOrCreate($condition, $data);
        }
    }

    protected function createTreePermissions()
    {
        $routes = $this->getRoutes();
        $groupRoutes = $this->getGroupRoutes($routes);
        foreach ($groupRoutes as $key => $children) {
            $title = Str::afterLast($key, '\\');
            $parent = Permission::updateOrCreate(['name' => $key], ['name' => $key, 'title' => $title]);
            foreach ($children as $route) {
                $title = Str::afterLast($route['action'], '\\');
                $condition = ['method' => strtolower($route['method']), 'url' => $route['uri'],];
                $data = array_merge($condition, ['pid' => $parent->id, 'name' => join(',', $condition), 'title' => $title]);
                Permission::updateOrCreate($condition, $data);
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

        if (($this->option('name') && !Str::contains($route['name'], $this->option('name'))) ||
            $this->option('path') && !Str::contains($route['uri'], $this->option('path')) ||
            $this->option('method') && !Str::contains($route['method'], strtoupper($this->option('method')))) {
            return false;
        }

        if ($this->option('except-path')) {
            foreach (explode(',', $this->option('except-path')) as $path) {
                if (Str::contains($route['uri'], $path)) {
                    return false;
                }
            }
        }

        return $route;
    }
}
