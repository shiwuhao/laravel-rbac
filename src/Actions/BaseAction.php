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
        $action->context = new ActionContext($data, $args);

        $validated = $action->validate($data);
        $action->context = new ActionContext($validated, $args);

        return $action->execute();
    }

    /**
     * 实例调用入口（支持依赖注入）
     *
     * @param  array  $data  数据数组
     * @param  mixed  ...$args  额外参数
     */
    public function __invoke(array $data = [], ...$args): mixed
    {
        if (empty($data) && function_exists('request')) {
            $data = request()->all();
        }

        try {
            $validated = $this->validate($data);
            $this->context = new ActionContext($validated, $args);

            $result = $this->execute();

            return $this->response->success($result);
        } catch (\Throwable $e) {
            return $this->response->failure($e->getMessage());
        }
    }
}
