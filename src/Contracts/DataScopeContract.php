<?php

namespace Rbac\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface DataScopeContract
{
    public function applyScope(Builder $query, $user): Builder;
    public function canAccess(Model $model, $user): bool;
}