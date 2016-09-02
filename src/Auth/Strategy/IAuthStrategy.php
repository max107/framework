<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:43
 */

namespace Mindy\Auth\Strategy;

use Mindy\Auth\IAuthProvider;
use Mindy\Auth\IUser;

/**
 * Interface IAuthStrategy
 * @package Mindy\Auth\Strategy
 */
interface IAuthStrategy
{
    /**
     * @param IUser $user
     * @param array $attributes
     * @return bool
     */
    public function process(IUser $user, array $attributes);

    /**
     * @return array
     */
    public function getErrors() : array;

    /**
     * @param IAuthProvider $provider
     * @return mixed
     */
    public function setAuthProvider(IAuthProvider $provider);

    /**
     * @return IAuthProvider
     */
    public function getAuthProvider() : IAuthProvider;

    /**
     * @return IUser
     */
    public function getUser() : IUser;
}