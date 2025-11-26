<?php

namespace Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 清理孤立的实例权限
 * - 当资源模型被删除或不存在时，移除对应的实例权限及其关联
 */
class CleanOrphanedPermissionsCommand extends Command
{
    /** @var string */
    protected $signature = 'rbac:clean-orphaned-permissions
        {--dry : 仅预览将被清理的权限，不执行删除}
        {--include-soft-deletes : 包含软删除的资源实例作为孤立项}
        {--chunk=500 : 每批处理的记录数量}';

    /** @var string */
    protected $description = 'Scan and remove orphaned instance permissions (resource_type/resource_id not existing)';

    public function handle(): int
    {
        $permissionModel = config('rbac.models.permission');
        $tables = config('rbac.tables');

        $dry = (bool) $this->option('dry');
        $includeSoftDeletes = (bool) $this->option('include-soft-deletes');
        $chunk = (int) $this->option('chunk');

        $totalChecked = 0;
        $totalOrphaned = 0;
        $orphans = [];

        $this->info('Scanning instance permissions...');

        $permissionModel::whereNotNull('resource_type')
            ->whereNotNull('resource_id')
            ->orderBy('id')
            ->chunk($chunk, function ($permissions) use (&$totalChecked, &$totalOrphaned, &$orphans, $includeSoftDeletes) {
                foreach ($permissions as $permission) {
                    $totalChecked++;

                    $class = $permission->resource_type;
                    $id = $permission->resource_id;

                    // 资源模型类不存在，视为孤立
                    if (!class_exists($class)) {
                        $orphans[] = $permission->id;
                        $totalOrphaned++;
                        continue;
                    }

                    // 检查资源是否存在
                    $exists = false;
                    try {
                        /** @var \Illuminate\Database\Eloquent\Model $model */
                        $model = new $class;
                        $query = $model->newQuery();
                        if (!$includeSoftDeletes && method_exists($model, 'bootSoftDeletes')) {
                            // 如果模型启用了软删除，默认排除已软删除记录
                            $query->whereNull($model->getQualifiedDeletedAtColumn());
                        }
                        $exists = $query->whereKey($id)->exists();
                    } catch (\Throwable $e) {
                        // 构建查询失败，视为孤立
                        $exists = false;
                    }

                    if (!$exists) {
                        $orphans[] = $permission->id;
                        $totalOrphaned++;
                    }
                }
            });

        if (empty($orphans)) {
            $this->info('No orphaned instance permissions found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$totalOrphaned} orphaned permissions (checked {$totalChecked}).");

        if ($dry) {
            $this->line('Dry-run mode: showing IDs only.');
            $this->line('Orphaned IDs: ' . implode(',', $orphans));
            return self::SUCCESS;
        }

        // 执行清理
        $this->info('Cleaning orphaned permissions...');
        DB::transaction(function () use ($orphans, $tables, $permissionModel) {
            // 先清关联，再删权限
            DB::table($tables['role_permission'])->whereIn('permission_id', $orphans)->delete();
            DB::table($tables['user_permission'])->whereIn('permission_id', $orphans)->delete();
            DB::table($tables['permission_data_scope'])->whereIn('permission_id', $orphans)->delete();
            $permissionModel::whereIn('id', $orphans)->delete();
        });

        $this->info('Cleanup finished. Removed IDs: ' . implode(',', $orphans));
        return self::SUCCESS;
    }
}
