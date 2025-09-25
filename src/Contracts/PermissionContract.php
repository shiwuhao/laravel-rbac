<?php

namespace Rbac\Contracts;

interface PermissionContract
{
    public function roles();
    public function users();
}