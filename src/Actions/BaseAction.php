<?php

namespace Rbac\Actions;

use Illuminate\Support\Facades\Validator;
use Rbac\Contracts\ResponseFormatter;

/**
 * Action 基类
 *
 * 使用上下文模式统一 Action 执行流程
 */
abstract class BaseAction
{
    protected ResponseFormatter $response;

    protected ActionContext $context;

    public function __construct()
    {
        $this->response = app(config('rbac.response_formatter'));
    }

    /**
     * 执行核心业务逻辑
     *
     * 子类必须实现此方法，通过 $this->context 访问数据
     */
    abstract protected function execute(): mixed;

    /**
     * 定义验证规则
     *
     * 可在子类中访问 $this->context 获取动态规则
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * 验证数据
     *
     * @param  array  $data  待验证的数据
     * @return array 验证后的数据
     */
    public function validate(array $data): array
    {
        $rules = $this->rules();
        $validator = Validator::make($data, $rules);

        return $validator->validate();
    }

    /**
     * 应用查询过滤器（从配置读取）
     *
     * @param  array  $params  查询参数
     */
    protected function applyQueryFilter(\Illuminate\Database\Eloquent\Builder $query, array $params): \Illuminate\Database\Eloquent\Builder
    {
        $filter = config('rbac.query_filter');

        // 如果配置了过滤器且是闭包，则执行
        if ($filter instanceof \Closure) {
            return $filter($query, $params);
        }

        return $query;
    }

    /**
     * 获取分页大小（限制最大值）
     *
     * @param  int  $default  默认值
     * @param  int  $max  最大值
     */
    protected function getPerPage(int $default = 15, int $max = 100): int
    {
        $perPage = $this->context->data('per_page', $default);

        return min($perPage, $max);
    }

    /**
     * 静态调用入口
     *
     * @param  array  $data  数据数组
     * @param  mixed  ...$args  额外参数（如 ID）
     *
     * @example
     * UpdateRole::handle($request->all(), $roleId);
     * CreateRole::handle($request->all());
     */
    public static function handle(array $data = [], ...$args): mixed
    {
        $action = app(static::class);
        $action->context = new ActionContext($data, $args, $data);

        $validated = $action->validate($data);
        $action->context = new ActionContext($validated, $args, $data);

        return $action->execute();
    }

    /**
     * 实例调用入口（支持依赖注入）
     *
     * 处理路由参数：当第一个参数是路径参数（如 {id}）时，自动从 request 获取数据
     *
     * @param  mixed  $dataOrId  数据数组或路径参数（如 id）
     * @param  mixed  ...$args  额外参数
     */
    public function __invoke(mixed $dataOrId = null, ...$args): mixed
    {
        // 区分路径参数和数据数组
        if (is_array($dataOrId)) {
            $data = $dataOrId;
        } else {
            // 路径参数场景：从 request 获取数据，路径参数作为额外参数
            $data = request()->all();
            if ($dataOrId !== null) {
                array_unshift($args, $dataOrId);
            }
        }

        try {
            // 验证数据，但保留原始数据用于过滤器
            $validated = $this->validate($data);
            $this->context = new ActionContext($validated, $args, $data);

            $result = $this->execute();

            return $this->response->success($result);
        } catch (\Throwable $e) {
            return $this->response->failure($e->getMessage());
        }
    }
}
