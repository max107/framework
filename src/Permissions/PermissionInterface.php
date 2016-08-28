<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:03
 */

declare(strict_types = 1);

namespace Mindy\Permissions;

interface PermissionInterface
{
    public function can($code) : bool;
}