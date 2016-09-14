<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:58
 */

namespace Mindy\Auth\UserProvider;

/**
 * Class ChainUserProvider
 * @package Mindy\Auth\UserProvider
 */
class ChainUserProvider implements UserProviderInterface
{
    /**
     * @var array
     */
    protected $providers;

    /**
     * ChainUserProvider constructor.
     * @param array $userProviders
     */
    public function __construct(array $userProviders)
    {
        $this->providers = $userProviders;
    }

    /**
     * @param array $attributes
     * @return null|\Mindy\Auth\UserInterface
     */
    public function get(array $attributes)
    {
        foreach ($this->providers as $provider) {
            if (($user = $provider->get($attributes))) {
                return $user;
            }
        }

        return null;
    }
}