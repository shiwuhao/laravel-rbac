<h1 align="center"> laravel-rbac </h1>

<p> laravel-rbac是一个基于Laravel框架的扩展包。</p>
<p>该扩展包为Laravel框架提供了RBAC模型的实现，并且支持模型授权，比如对菜单，分类等模型的授权。</p>
<p>Permission模型为一对一多态模型，默认提供Action模型的授权管理，并根据路由文件自动生成Action模型的权限节点。如需扩展其他模型，新建模型后，添加Shiwuhao\Rbac\Models\Traits\PermissibleTrait即可，会自动同步模型节点到permissions表中。</p>

## 版本信息

Rbac  | Laravel | PHP
:------|:--------|:--------
1.0.1.beta-1 | > 7.x   | > =7.x

## 安装方法

#### 使用composer快速安装扩展包

```shell
$ composer require shiwuhao/laravel-rbac -vvv
```

## 配置信息

#### 发布配置文件

```shell
php artisan vendor:publish
```

会生成以下两个文件<br>
config/rbac.php<br>
database/create_rbac_tables.php<br>

#### 数据迁移

```shell
php artisan migrate
```

迁移后，将出现四个新表：<br/>
roles -- 角色表<br/>
actions -- 操作表<br/>
permissions -- 权限表<br/>
role_user -- 角色和用户之间的多对多关系表<br/>
role_permission -- 角色和权限之间的多对多关系表<br/>

## 模型

#### Role

创建角色模型 app/Models/Role.php，继承\Shiwuhao\Rbac\Models\Role

```php
<?php

namespace App\Models;

class Role extends \Shiwuhao\Rbac\Models\Role
{

}
```

#### Permission

创建权限模型 app/Models/Permission.php，继承\Shiwuhao\Rbac\Models\Permission

```php
<?php

namespace App\Models;

class Permission extends \Shiwuhao\Rbac\Models\Permission
{

}
```

#### Action

创建操作模型 app/Models/Action.php，继承\Shiwuhao\Rbac\Models\Action

```php
<?php

namespace App\Models;

class Action extends \Shiwuhao\Rbac\Models\Action
{

}
```

#### User

用户模型中 添加 UserTrait

```php
<?php

namespace App\Models;

use Shiwuhao\Rbac\Models\Traits\UserTrait;

class User extends Authenticatable
{
    use UserTrait; // 添加这个trait到你的User模型中
}

```

#### 扩展模型授权，比如菜单 Menu

创建菜单模型 app/Models/Menu.php，使用 Shiwuhao\Rbac\Models\Traits\PermissibleTrait。Menu模型的增删改会自动同步到permissions表中。

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shiwuhao\Rbac\Models\Traits\PermissibleTrait;

class Menu extends Model
{
    use PermissibleTrait;
}

```

## 使用

### Action 权限节点

Action和Permission为一对一多态模型，创建Action节点会自动同步到Permission模型

#### 创建一个Action节点

```php
$action = new App\Models\Action();
$action->name= 'user:index';
$action->label= '用户列表';
$action->method= 'get';
$action->uri= 'backend/users';
$action->save();
```

#### 批量生成Action节点

<p>基于当前路由批量生成Action权限节点，可在config/rbac.php配置文件中通过path，except_path指定路径</p>

```shell
php artisan rbac:auto-generate-actions
```

### Role 角色

#### 创建一个角色

```php
$role = new App\Models\Role();
$role->name= 'Administrator';
$role->label= '超级管理员';
$role->remark= '备注';
$role->save();
```

#### 给角色绑定权限和用户

```php
$role = App\Models\Role::find(1);

// 绑定权限
$role->permissions()->sync([1, 2, 3, 4]); // 同步
$role->permissions()->attach(5);// 附加
$role->permissions()->detach(2);// 分离

// 绑定用户
$role->users()->sync([1, 2, 3, 4]);// 同步
$role->users()->attach(5);// 附加
$role->users()->detach(5);// 分离
```

### User 用户

#### 获取用户角色

```php
$user = App\Models\User::find(1);
$user->roles;
```

#### 给用户绑定角色

```php
$user->roles()->sync([1, 2, 3, 4]);// 同步
$user->roles()->attach(5);// 附加
$user->roles()->detach(5);// 分离
```

#### 获取用户拥有的权限节点

```php
$user->permissions;
```

<p>返回数据为Collection集合，转数组可直接使用->toArray()</p>

## 鉴权

```php
$user->hasPermission($alias, $type = 'alias');
$user->hasPermissionAlias('get,backend/users');// action权限节点默认使用别名鉴权
$user->hasPermissionName('user:index');
```

## Middleware

在app/Http/Kernel.php文件中添加路由中间件

```php
protected $routeMiddleware = [
    'permission' => Shiwuhao\Rbac\Middleware\PermissionMiddleware,
];
```

添加后即可在路由中使用

```php
Route::middleware('permission')->group(function () {
    Route::prefix('backend')->group(function () {
        Route::get('users', [UserController::class, 'index']);
    });
});
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/shiwuhao/laravel-rbac/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/shiwuhao/laravel-rbac/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and
PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT