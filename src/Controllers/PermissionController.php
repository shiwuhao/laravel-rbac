<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\ApiResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PermissionController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Permission::ofSearch($request->all())->withCount('children')->latest('sort');
        if ($request->get('pid')) {
            $permissions = $query->get();
        } elseif ($request->get('name') || $request->get('title')) {
            $permissions = $query->paginate();
        } else {
            $permissions = $query->OfParent()->get();
        }

        return ApiResource::collection($permissions);
    }

    /**
     * @param Request $request
     * @return ApiResource
     */
    public function store(Request $request): ApiResource
    {
        $permission = new Permission($request->all());
        $permission->save();

        return ApiResource::make($permission);
    }

    /**
     * @param Permission $permission
     * @return ApiResource
     */
    public function show(Permission $permission): ApiResource
    {
        return ApiResource::make($permission);
    }

    /**
     * @param Request $request
     * @param Permission $permission
     * @return ApiResource
     */
    public function update(Request $request, Permission $permission): ApiResource
    {
        $permission->fill($request->all());
        $permission->save();

        return ApiResource::make($permission);
    }

    /**
     * @param Permission $permission
     * @return ApiResource
     * @throws ApiException
     */
    public function destroy(Permission $permission): ApiResource
    {
        if ($permission->children()->count() > 0) {
            throw new ApiException('当前节点下还有子集节点');
        }

        $permission->delete();

        return ApiResource::make([]);
    }

    /**
     * 自动生成权限节点
     * @return string
     */
    public function autoGenerate()
    {
        $path = 'backend';
        $exceptPath = ['backend/uploads'];
        Artisan::call('rbac:generate-permissions', [
            '--path' => $path,
            '--except-path' => join(',', $exceptPath)
        ]);

        return 'success';
    }
}
