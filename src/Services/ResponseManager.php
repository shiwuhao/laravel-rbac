<?php

namespace Rbac\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 响应管理器
 * 
 * 根据配置和请求类型决定返回API响应还是视图响应
 */
class ResponseManager
{
    /**
     * 判断是否应该返回API响应
     */
    public function shouldReturnApi(Request $request): bool
    {
        $responseMode = config('rbac.response_mode', 'auto');
        
        switch ($responseMode) {
            case 'api':
                return true;
                
            case 'view':
                return false;
                
            case 'hybrid':
                $apiPrefix = config('rbac.api_prefix', 'api');
                return $request->is($apiPrefix . '/*');
                
            case 'auto':
            default:
                return $request->wantsJson() || 
                       $request->is('api/*') || 
                       $request->ajax() ||
                       $request->header('Accept') === 'application/json';
        }
    }

    /**
     * 处理成功响应
     */
    public function success(
        Request $request, 
        $data = null, 
        string $message = '操作成功', 
        string $redirectRoute = null,
        string $viewName = null,
        array $viewData = []
    ) {
        if ($this->shouldReturnApi($request)) {
            return $this->apiSuccess($data, $message);
        }

        return $this->viewSuccess($message, $redirectRoute, $viewName, $viewData);
    }

    /**
     * 处理错误响应
     */
    public function error(
        Request $request,
        string $message = '操作失败',
        int $code = 422,
        array $errors = [],
        bool $redirectBack = true,
        string $viewName = null,
        array $viewData = []
    ) {
        if ($this->shouldReturnApi($request)) {
            return $this->apiError($message, $code, $errors);
        }

        return $this->viewError($message, $redirectBack, $viewName, $viewData);
    }

    /**
     * API成功响应
     */
    protected function apiSuccess($data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * API错误响应
     */
    protected function apiError(string $message = '操作失败', int $code = 422, array $errors = []): JsonResponse
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
     * 视图成功响应
     */
    protected function viewSuccess(
        string $message, 
        string $redirectRoute = null, 
        string $viewName = null, 
        array $viewData = []
    ) {
        if ($viewName) {
            return view($viewName, array_merge($viewData, ['message' => $message]));
        }

        if ($redirectRoute) {
            return redirect()->route($redirectRoute)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * 视图错误响应
     */
    protected function viewError(
        string $message, 
        bool $redirectBack = true, 
        string $viewName = null, 
        array $viewData = []
    ) {
        if ($viewName) {
            return view($viewName, array_merge($viewData, ['error' => $message]));
        }

        if ($redirectBack) {
            return back()->withInput()->withErrors(['error' => $message]);
        }

        return redirect()->back()->withErrors(['error' => $message]);
    }

    /**
     * 处理分页响应
     */
    public function paginated(
        Request $request,
        $paginator,
        string $message = '数据获取成功',
        string $viewName = null,
        array $viewData = []
    ) {
        if ($this->shouldReturnApi($request)) {
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

        // 视图响应
        $data = array_merge($viewData, [
            'data' => $paginator->items(),
            'paginator' => $paginator
        ]);

        return view($viewName, $data);
    }
}