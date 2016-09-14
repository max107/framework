<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:58
 */

namespace Mindy\Permissions\PermissionProvider;

/**
 * Class ChainPermissionProvider
 * @package Mindy\Permissions\PermissionProvider
 */
class ChainPermissionProvider extends AbstractPermissionProvider
{
    /**
     * @var array
     */
    protected $providers;

    /**
     * ChainPermissionProvider constructor.
     * @param array $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return array
     */
    public function load() : array
    {
        $permissions = [];
        foreach ($this->providers as $provider) {
            $permissions = array_merge($permissions, $provider->load());
        }
        return $permissions;
    }
}