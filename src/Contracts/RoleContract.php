<?php

namespace Rbac\Contracts;

interface RoleContract
{
    public function permissions();
    public function hasPermission(string|\Rbac\Models\Permission $permission): bool;
    public function givePermission(string|array|\Rbac\Models\Permission $permissions);
    public function revokePermission(string|array|\Rbac\Models\Permission $permissions);
}