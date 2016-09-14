<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:23
 */

namespace Mindy\Auth\UserProvider;

use Mindy\Auth\AuthProviderInterface;

abstract class AbstractUserProvider implements UserProviderInterface
{
    /**
     * @var AuthProviderInterface
     */
    protected $authProvider;

    /**
     * MemoryUserProvider constructor.
     * @param AuthProviderInterface $authProvider
     */
    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }
}