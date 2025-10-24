<?php

namespace Rbac\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PermissionGroup
{
    public function __construct(public string $slug, public ?string $name = null)
    {
    }
}