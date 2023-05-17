<?php

namespace Shiwuhao\Rbac\Models;


use Illuminate\Database\Eloquent\Model;
use Shiwuhao\Rbac\Contracts\PermissibleInterface;
use Shiwuhao\Rbac\Models\Traits\PermissibleTrait;

/**
 * Action Model
 */
class Action extends Model implements PermissibleInterface
{
    use PermissibleTrait;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name', 'label', 'method', 'uri',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'alias'
    ];

    /**
     * Role constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('rbac.table.actions'));
    }

    /**
     * alias
     * @return string
     */
    public function getAliasAttribute(): string
    {
        return $this->method . ',' . $this->uri;
    }
}
