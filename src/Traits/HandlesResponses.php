<?php

namespace Rbac\Traits;

use Rbac\Services\ResponseManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 响应处理特性
 * 
 * 为控制器提供统一的响应处理方法
 */
trait HandlesResponses
{
    protected ResponseManager $responseManager;

    /**
     * 获取响应管理器实例
     */
    protected function getResponseManager(): ResponseManager
    {
        if (!isset($this->responseManager)) {
            $this->responseManager = app(ResponseManager::class);
        }

        return $this->responseManager;
    }

    /**
     * 处理Action响应
     * 
     * 将Action的JSON响应转换为适当的响应类型
     */
    protected function handleActionResponse(
        Request $request,
        JsonResponse $actionResponse,
        string $successRedirectRoute = null,
        string $viewName = null,
        array $viewData = []
    ) {
        $responseManager = $this->getResponseManager();
        
        // 如果应该返回API响应，直接返回Action的响应
        if ($responseManager->shouldReturnApi($request)) {
            return $actionResponse;
        }

        // 解析Action响应数据
        $responseData = json_decode($actionResponse->getContent(), true);
        $isSuccess = $responseData['success'] ?? false;
        $message = $responseData['message'] ?? '';
        $data = $responseData['data'] ?? null;

        if ($isSuccess) {
            return $this->handleSuccessResponse(
                $request, 
                $data, 
                $message, 
                $successRedirectRoute, 
                $viewName, 
                $viewData
            );
        } else {
            return $this->handleErrorResponse(
                $request, 
                $message, 
                $actionResponse->getStatusCode(),
                $responseData['errors'] ?? []
            );
        }
    }

    /**
     * 处理成功响应
     */
    protected function handleSuccessResponse(
        Request $request,
        $data = null,
        string $message = '操作成功',
        string $redirectRoute = null,
        string $viewName = null,
        array $viewData = []
    ) {
        $responseManager = $this->getResponseManager();
        
        return $responseManager->success(
            $request,
            $data,
            $message,
            $redirectRoute,
            $viewName,
            $viewData
        );
    }

    /**
     * 处理错误响应
     */
    protected function handleErrorResponse(
        Request $request,
        string $message = '操作失败',
        int $code = 422,
        array $errors = []
    ) {
        $responseManager = $this->getResponseManager();
        
        return $responseManager->error(
            $request,
            $message,
            $code,
            $errors
        );
    }

    /**
     * 处理分页响应
     */
    protected function handlePaginatedResponse(
        Request $request,
        $paginator,
        string $message = '数据获取成功',
        string $viewName = null,
        array $viewData = []
    ) {
        $responseManager = $this->getResponseManager();
        
        return $responseManager->paginated(
            $request,
            $paginator,
            $message,
            $viewName,
            $viewData
        );
    }

    /**
     * 获取视图名称
     * 
     * 根据控制器和方法自动生成视图名称
     */
    protected function getViewName(string $action = null): string
    {
        $controllerName = strtolower(str_replace('Controller', '', class_basename($this)));
        $action = $action ?? debug_backtrace()[1]['function'];
        
        return "rbac::{$controllerName}.{$action}";
    }
}