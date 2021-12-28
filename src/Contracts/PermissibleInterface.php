<?php

namespace Shiwuhao\Rbac\Contracts;


use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Interface PermissibleInterface
 * @package Shiwuhao\Rbac\Contracts
 */
interface PermissibleInterface
{
    /**
     * @return MorphOne
     */
    public function permission(): MorphOne;

    /**
     * @return string
     */
    public function getAliasAttribute(): string;
}
