<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:43
 */

namespace Mindy\Auth\Strategy;

use Mindy\Auth\AuthProviderInterface;
use Mindy\Auth\UserInterface;

/**
 * Interface IAuthStrategy
 * @package Mindy\Auth\Strategy
 */
interface AuthStrategyInterface
{
    /**
     * @param UserInterface $user
     * @param array $attributes
     * @return bool
     */
    public function process(UserInterface $user, array $attributes);

    /**
     * @return array
     */
    public function getErrors() : array;

    /**
     * @return UserInterface
     */
    public function getUser() : UserInterface;
}