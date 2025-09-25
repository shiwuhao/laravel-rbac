<?php

namespace Rbac\Actions\Permission;

use Rbac\Actions\BaseAction;
use Rbac\Models\Permission;
use Rbac\Services\RbacService;
use Rbac\Enums\GuardType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * 批量创建权限 Action
 */
class BatchCreatePermissionsAction extends BaseAction
{
    protected RbacService $rbacService;

    public function __construct(RbacService $rbacService)
    {
        $this->rbacService = $rbacService;
    }

    /**
     * 执行批量创建权限操作
     * 
     * @param Request $request 请求对象
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            // 验证请求数据
            $validator = Validator::make($request->all(), [
                'resource' => 'required|string|max:100',
                'actions' => 'required|array|min:1',
                'actions.*' => 'string|max:50',
                'guard_name' => 'required|string|in:web,api',
                'description_template' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // 使用服务创建资源权限
            $permissions = $this->rbacService->createResourcePermissions(
                $validated['resource'],
                $validated['actions'],
                GuardType::from($validated['guard_name']),
                $validated['description_template'] ?? null
            );

            $this->log('batch_create_permissions', [
                'resource' => $validated['resource'],
                'actions' => $validated['actions'],
                'count' => $permissions->count()
            ]);

            return $this->success(
                $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                        'resource' => $permission->resource,
                        'action' => $permission->action,
                    ];
                }),
                '权限批量创建成功',
                201
            );

        } catch (\Throwable $e) {
            return $this->handleException($e, 'BatchCreatePermissions');
        }
    }
}