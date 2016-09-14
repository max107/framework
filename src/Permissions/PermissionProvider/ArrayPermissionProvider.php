<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 17:04
 */

namespace Mindy\Permissions\PermissionProvider;

class ArrayPermissionProvider extends AbstractPermissionProvider
{
    /**
     * @var array
     */
    protected $permissions = [];

    /**
     * ArrayPermissionProvider constructor.
     * @param array $permissions
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return array
     */
    public function load() : array
    {
        return $this->permissions;
    }
}