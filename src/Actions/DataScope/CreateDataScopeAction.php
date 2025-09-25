<?php

namespace Rbac\Actions\DataScope;

use Rbac\Actions\BaseAction;
use Rbac\Models\DataScope;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * 创建数据范围 Action
 */
class CreateDataScopeAction extends BaseAction
{
    /**
     * 执行创建数据范围操作
     * 
     * @param Request $request 请求对象
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            // 验证请求数据
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'type' => 'required|string|in:all,custom,department,department_and_sub,only_self',
                'rules' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // 创建数据范围
            $dataScope = DataScope::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'rules' => $validated['rules'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->log('create_data_scope', [
                'data_scope_id' => $dataScope->id,
                'name' => $dataScope->name
            ]);

            return $this->success($dataScope, '数据范围创建成功', 201);

        } catch (\Throwable $e) {
            return $this->handleException($e, 'CreateDataScope');
        }
    }
}