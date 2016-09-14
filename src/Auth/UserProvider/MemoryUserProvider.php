<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:08
 */

namespace Mindy\Auth\UserProvider;

/**
 * Class MemoryUserProvider
 * @package Mindy\Auth\UserProvider
 */
class MemoryUserProvider extends AbstractUserProvider
{
    /**
     * @var array
     */
    protected $users;

    /**
     * @param array $users
     */
    public function setUsers(array $users = [])
    {
        $this->users = $users;
    }

    /**
     * @param array $attributes
     * @return null|\Mindy\Auth\UserInterface
     */
    public function get(array $attributes)
    {
        foreach ($this->users as $user) {
            if (count(array_intersect($user, $attributes)) == count($attributes)) {
                return $this->authProvider->createUser($user);
            }
        }

        return null;
    }
}