<?php
// 简单测试脚本，避免终端历史问题
require_once __DIR__ . '/vendor/autoload.php';

echo "检查 InstallCommand...\n";

try {
    if (class_exists('\Rbac\Commands\InstallCommand')) {
        echo "✓ InstallCommand 类存在\n";
        $cmd = new \Rbac\Commands\InstallCommand();
        echo "✓ InstallCommand 可以实例化\n";
    } else {
        echo "✗ InstallCommand 类不存在\n";
    }

    // 检查服务提供者
    echo "检查服务提供者...\n";
    $container = new \Illuminate\Container\Container();
    $provider = new \Rbac\RbacServiceProvider($container);
    echo "✓ RbacServiceProvider 正常\n";

    echo "所有检查通过！\n";
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}