<h1 align="center"> laravel-rbac </h1>

<p align="center"> A laravel package.</p>

## 安装方法

使用composer快速安装扩展包

```shell
$ composer require shiwuhao/laravel-rbac -vvv
```

## 配置信息

### 发布配置文件

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

### 自动生成action权限节点

```shell
php artisan rbac:auto-generate-actions
```

## 使用方法

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/shiwuhao/laravel-rbac/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/shiwuhao/laravel-rbac/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and
PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT