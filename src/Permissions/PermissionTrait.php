<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 19:16
 */

namespace Mindy\Permissions;

use function Mindy\app;

/**
 * Class PermissionTrait
 * @package Mindy\Permissions
 */
trait PermissionTrait
{
    /**
     * @param string $code
     * @param array $params
     * @return bool
     */
    public function can(string $code, array $params = []) : bool
    {
        if (app()->hasComponent('permissions')) {
            return app()->permissions->can($this, $code, $params);
        }

        return true;
    }
}