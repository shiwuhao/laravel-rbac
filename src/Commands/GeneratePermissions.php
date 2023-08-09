<?php

namespace Shiwuhao\Rbac\Commands;


use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
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
    protected Router $router;

    /**
     * @var
     */
    protected mixed $config;

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
    public function handle(): void
    {
        $this->createActions();

        $this->info("Creation Successful");
    }

    /**
     *  批量创建Action权限节点
     */
    protected function createActions(): void
    {
        $this->getRoutes()->groupBy(function ($item) {
            list($controller, $action) = explode('@', $item['action']);
            return $controller;
        })->each(function ($group, $key) {
            $name = $this->replaceNameOnAction($key);
            $condition = ['action' => $key];
            $groupModel = app($this->config['model']['action'])->updateOrCreate($condition, array_merge($condition, ['name' => $name, 'label' => $name]));
            if ($groupModel) {
                $group->each(function ($route) use ($groupModel) {
                    $this->actionUpdateOrCreate($route, $groupModel->id);
                });
            }
        });
    }

    /**
     * 基于action生成name标识
     * @param $fullAction
     * @return string
     */
    protected function replaceNameOnAction($fullAction): string
    {
        return Str::replace($this->config['replace_action']['search'] ?? [], $this->config['replace_action']['replace'] ?? [], $fullAction);
    }

    /**
     * action create
     * @param $route
     * @param int $pid
     */
    protected function actionUpdateOrCreate($route, int $pid = 0): void
    {
        $name = $this->replaceNameOnAction($route['action']);
        $condition = ['action' => $route['action']];
        $actionModel = app($this->config['model']['action'])->updateOrCreate($condition, array_merge($condition, [
            'pid' => $pid,
            'uri' => $route['uri'],
            'method' => strtolower($route['method']),
            'name' => $name,
            'label' => $name,
        ]));
        $this->info($actionModel->method . ',' . $actionModel->uri);
    }

    /**
     * @return Collection
     */
    protected function getRoutes(): Collection
    {
        return collect($this->router->getRoutes())->map(function ($route) {
            return $this->filterRoute([
                'domain' => $route->domain(),
                'method' => $route->methods()[0],
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => ltrim($route->getActionName(), '\\'),
            ]);
        })->filter();
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
                if (Str::contains($route['uri'], trim($path, '/'))) {
                    return false;
                }
            }
        }

        $paths = array_merge($this->option('path') ? explode(',', $this->option('path')) : [], config('rbac.path'));
        if ($paths) {
            foreach ($paths as $path) {
                if (Str::contains($route['uri'], trim($path, '/'))) {
                    return $route;
                }
            }
        }

        return false;
    }
}