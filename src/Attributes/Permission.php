<?php

namespace Rbac\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Permission
{
    public function __construct(public string $slug, public ?string $name = null)
    {
    }
}