#!/bin/bash

echo "=== Laravel RBAC 包安装问题修复脚本 ==="
echo

# 1. 重新生成 composer autoload
echo "1. 重新生成 composer autoload..."
composer dump-autoload

# 2. 验证 InstallCommand 类
echo "2. 验证 InstallCommand 类..."
php -r "
require_once 'vendor/autoload.php';
if (class_exists('\\Rbac\\Commands\\InstallCommand')) {
    echo '✓ InstallCommand 类加载正常' . PHP_EOL;
} else {
    echo '✗ InstallCommand 类加载失败' . PHP_EOL;
    exit(1);
}
"

# 3. 验证所有命令类
echo "3. 验证所有命令类..."
php -r "
require_once 'vendor/autoload.php';
\$commands = [
    'InstallCommand' => '\\Rbac\\Commands\\InstallCommand',
    'SeedTestDataCommand' => '\\Rbac\\Commands\\SeedTestDataCommand', 
    'QuickSeedCommand' => '\\Rbac\\Commands\\QuickSeedCommand'
];

foreach (\$commands as \$name => \$class) {
    if (class_exists(\$class)) {
        echo '✓ ' . \$name . ' 正常' . PHP_EOL;
    } else {
        echo '✗ ' . \$name . ' 失败' . PHP_EOL;
    }
}
"

# 4. 验证服务提供者
echo "4. 验证服务提供者..."
php -r "
require_once 'vendor/autoload.php';
try {
    \$container = new \\Illuminate\\Container\\Container();
    \$provider = new \\Rbac\\RbacServiceProvider(\$container);
    echo '✓ RbacServiceProvider 可以正常实例化' . PHP_EOL;
} catch (Exception \$e) {
    echo '✗ RbacServiceProvider 实例化失败: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

echo
echo "=== 修复完成 ==="
echo "现在您可以在 Laravel 项目中执行以下步骤："
echo "1. composer require shiwuhao/laravel-rbac"
echo "2. php artisan rbac:install"
echo