<?php
/**
 * Created by PhpStorm.
 * User: shiwuhao
 * Date: 2019/3/14
 * Time: 5:42 PM
 */

namespace Shiwuhao\Rbac\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Trait UserTrait
 * @package Shiwuhao\Rbac\Traits
 */
trait BaseTrait
{

    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param  mixed $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            return [$value->{$this->getKeyName()}];
        }

        if ($value instanceof Collection) {
            return $value->pluck($this->getKeyName())->all();
        }

        if ($value instanceof BaseCollection) {
            return $value->toArray();
        }

        return (array)$value;
    }
}
