<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:55
 */

namespace Mindy\Permissions\PermissionProvider;

/**
 * Interface PermissionProviderInterface
 * @package Mindy\Permissions\PermissionProvider
 */
interface PermissionProviderInterface
{
    /**
     * @return array
     */
    public function load() : array;
}