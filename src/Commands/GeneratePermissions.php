<?php

namespace Shiwuhao\Rbac\Commands;


use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;


/**
 * Class GeneratePermissions
 * @package Shiwuhao\Rbac\Commands
 */
class GeneratePermissions extends Command
{

    /**
     * @var string
     */
    protected $signature = 'rbac:auto-generate-actions
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
     * @var
     */
    protected $config;

    /**
     * GeneratePermissions constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;
        $this->config = config('rbac');
    }

    /**
     * handle
     */
    public function handle()
    {
        $this->createActions();

        $this->info("Creation Successful");
    }

    /**
     *  批量创建Action权限节点
     */
    protected function createActions()
    {
        $routes = $this->getRoutes();

        foreach ($routes as $route) {
            $this->actionUpdateOrCreate($route);
        }
    }

    /**
     * action create
     * @param $route
     */
    protected function actionUpdateOrCreate($route)
    {
        $condition = ['uri' => $route['uri'], 'method' => strtolower($route['method'])];
        $actionModel = app($this->config['model']['action']);
        $actionModel = $actionModel->updateOrCreate($condition, array_merge($condition, [
            'name' => $this->getActionName($route['action']),
            'label' => $this->getActionLabel($route['action']),
        ]));
        $this->info($actionModel->method . ',' . $actionModel->uri);
    }

    /**
     * action label
     * @param $action
     * @return string
     */
    protected function getActionLabel($action): string
    {
        list($controller, $action) = explode('@', $action);
        $controllerLabel = $this->config['controller_label_replace'][$controller] ?? $controller . '@';
        $actionLabel = $this->config['action_label_replace'][$action] ?? $action;
        return $controllerLabel . $actionLabel;
    }

    /**
     * action name
     * @param $action
     * @return string
     */
    protected function getActionName($action): string
    {
        $name = Str::replace('Controller@', ':', Str::afterLast($action, '\\'));
        return Str::snake($name, '-');
    }

    /**
     * 获取路由
     * @return array
     */
    protected function getRoutes(): array
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
     * 获取分组路由
     * @param $routes
     * @return array
     */
    protected function getGroupRoutes($routes): array
    {
        return collect($routes)->mapToGroups(function ($item) {
            return [Str::before($item['action'], '@') => $item];
        })->toArray();
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
                    return $route;
                }
            }
        }

        return false;
    }
}
