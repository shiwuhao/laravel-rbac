<h1 align="center"> laravel-rbac </h1>

<p align="center"> A laravel package.</p>

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
database/rbac_table.php<br>

### 数据迁移

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

### Role

创建角色模型 app/Models/Role.php

```php
<?php

namespace App\Models;

class Role extends \Shiwuhao\Rbac\Models\Role
{

}
```

### Permission

创建权限模型 app/Models/Permission.php

```php
<?php

namespace App\Models;

class Permission extends \Shiwuhao\Rbac\Models\Permission
{

}
```

### Action

创建操作模型 app/Models/Action.php

```php
<?php

namespace App\Models;

class Action extends \Shiwuhao\Rbac\Models\Action
{

}
```

### User

###### 用户模型中 添加 UserTrait

```php
<?php

namespace App\Models;

use Shiwuhao\Rbac\Models\Traits\UserTrait;

class User extends Authenticatable
{
    use UserTrait; // 添加这个trait到你的User模型中
}

```

## 使用

### Action 权限节点

Action和Permission为一对一多态模型，创建Action节点会自动同步到Permission模型

###### 创建一个Action节点

```php
$action = new App\Models\Action();
$action->name= 'user:index';
$action->label= '用户列表';
$action->method= 'get';
$action->uri= 'backend/users';
$action->save();
```

###### 批量生成Action节点

基于当前路由批量生成Action权限节点，可在config/rbac.php配置文件中通过path，except_path指定路径

```shell
php artisan rbac:auto-generate-actions
```

## Role 角色

###### 创建一个角色

```php
$role = new App\Models\Role();
$role->name= 'Administrator';
$role->label= '超级管理员';
$role->remark= '备注';
$role->save();
```

###### 给角色绑定权限和用户

```php
$role = App\Models\Role::find(1);

// 绑定权限
$role->permissions()->sync([1, 2, 3, 4]); // 同步
$role->permissions()->attach(5);// 附加
$role->permissions()->detach(2);// 分离

// 绑定用户
$role->users()->sync([1, 2, 3, 4]);// 同步关联
$role->users()->attach(5);// 附加
$role->users()->detach(5);// 分离
```

## User 用户

###### 获取用户角色

```php
$user = App\Models\User::find(1);
$user->roles;
```

###### 给用户绑定角色

```php
$user->roles()->sync([1, 2, 3, 4]);// 同步
$user->roles()->attach(5);// 附加
$user->roles()->detach(5);// 分离
```

#### 获取用户拥有的权限节点

```php
$user->roleWithPermissions;// 角色和节点
$user->roleWithPermissions()->get();// 同上
$user->getPermissions();// 去重后的权限节点列表集合
$user->getPermissionAlias();// 去重后的权限节点别名集合
```

返回数据为Collection集合，转数组可直接使用->toArray()

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/shiwuhao/laravel-rbac/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/shiwuhao/laravel-rbac/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and
PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT