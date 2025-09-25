<?php

namespace Rbac\Actions;

use Rbac\Actions\Contracts\ActionInterface;
use Rbac\Services\ResponseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

/**
 * Action 抽象基类
 * 
 * 提供通用的响应处理和错误处理方法
 */
abstract class BaseAction implements ActionInterface
{
    protected ResponseManager $responseManager;

    public function __construct(ResponseManager $responseManager = null)
    {
        $this->responseManager = $responseManager ?? app(ResponseManager::class);
    }
    /**
     * 成功响应
     * 
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @param int $code HTTP状态码
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $this->transformData($data);
        }

        return response()->json($response, $code);
    }

    /**
     * 错误响应
     * 
     * @param string $message 错误消息
     * @param int $code HTTP状态码
     * @param array $errors 具体错误信息
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', int $code = Response::HTTP_BAD_REQUEST, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * 验证失败响应
     * 
     * @param array $errors 验证错误
     * @return JsonResponse
     */
    protected function validationError(array $errors): JsonResponse
    {
        return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * 未找到资源响应
     * 
     * @param string $resource 资源名称
     * @return JsonResponse
     */
    protected function notFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->error($resource . ' not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * 权限不足响应
     * 
     * @param string $message 权限错误消息
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Access denied'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * 转换数据格式
     * 
     * @param mixed $data 原始数据
     * @return mixed
     */
    protected function transformData($data)
    {
        if ($data instanceof Model) {
            return $data->toArray();
        }

        if ($data instanceof Collection) {
            return $data->map(function ($item) {
                return $item instanceof Model ? $item->toArray() : $item;
            })->toArray();
        }

        return $data;
    }

    /**
     * 获取分页响应
     * 
     * @param \Illuminate\Contracts\Pagination\Paginator $paginator 分页器
     * @param string $message 响应消息
     * @return JsonResponse
     */
    protected function paginated($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * 记录操作日志
     * 
     * @param string $action 操作类型
     * @param array $context 上下文信息
     * @return void
     */
    protected function log(string $action, array $context = []): void
    {
        \Log::info("RBAC Action: {$action}", array_merge($context, [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));
    }

    /**
     * 处理异常
     * 
     * @param \Throwable $exception 异常对象
     * @param string $action 操作名称
     * @return JsonResponse
     */
    protected function handleException(\Throwable $exception, string $action = 'Action'): JsonResponse
    {
        \Log::error("RBAC {$action} failed", [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
        ]);

        if (app()->environment('local', 'testing')) {
            return $this->error($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->error('Internal server error', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}