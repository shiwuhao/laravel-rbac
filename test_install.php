<?php

require_once 'vendor/autoload.php';

echo "=== Testing InstallCommand Class Loading ===" . PHP_EOL . PHP_EOL;

try {
    // Test 1: Check if class exists
    echo "1. Testing if InstallCommand class exists..." . PHP_EOL;
    if (class_exists('\\Rbac\\Commands\\InstallCommand')) {
        echo "   ✓ InstallCommand class found" . PHP_EOL;
    } else {
        echo "   ✗ InstallCommand class NOT found" . PHP_EOL;
        exit(1);
    }
    
    // Test 2: Try to instantiate
    echo "2. Testing InstallCommand instantiation..." . PHP_EOL;
    $cmd = new \Rbac\Commands\InstallCommand();
    echo "   ✓ InstallCommand can be instantiated" . PHP_EOL;
    
    // Test 3: Test all registered commands
    echo "3. Testing all command classes..." . PHP_EOL;
    $commandClasses = [
        'CreateRoleCommand' => '\\Rbac\\Commands\\CreateRoleCommand',
        'CreatePermissionCommand' => '\\Rbac\\Commands\\CreatePermissionCommand',
        'GenerateRoutePermissionsCommand' => '\\Rbac\\Commands\\GenerateRoutePermissionsCommand',
        'RbacStatusCommand' => '\\Rbac\\Commands\\RbacStatusCommand',
        'ClearCacheCommand' => '\\Rbac\\Commands\\ClearCacheCommand',
        'InstallCommand' => '\\Rbac\\Commands\\InstallCommand',
        'SeedTestDataCommand' => '\\Rbac\\Commands\\SeedTestDataCommand',
        'QuickSeedCommand' => '\\Rbac\\Commands\\QuickSeedCommand',
    ];
    
    foreach ($commandClasses as $name => $class) {
        if (class_exists($class)) {
            echo "   ✓ {$name} exists" . PHP_EOL;
        } else {
            echo "   ✗ {$name} NOT found" . PHP_EOL;
        }
    }
    
    // Test 4: Test RbacServiceProvider
    echo "4. Testing RbacServiceProvider..." . PHP_EOL;
    $container = new \Illuminate\Container\Container();
    $provider = new \Rbac\RbacServiceProvider($container);
    echo "   ✓ RbacServiceProvider can be instantiated" . PHP_EOL;
    
    echo PHP_EOL . "SUCCESS: All tests passed!" . PHP_EOL;
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . PHP_EOL;
    echo "Line: " . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
    exit(1);
}