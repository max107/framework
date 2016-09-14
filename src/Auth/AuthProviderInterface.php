<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 16:03
 */

namespace Mindy\Auth;

use Exception;
use Mindy\Auth\PasswordHasher\IPasswordHasher;

/**
 * Interface IAuthProvider
 * @package Mindy\Auth
 */
interface AuthProviderInterface
{
    /**
     * @param string $hasher
     * @return IPasswordHasher
     * @throws Exception
     */
    public function getPasswordHasher(string $hasher) : IPasswordHasher;

    /**
     * @param array $attributes
     * @return UserInterface
     */
    public function createUser(array $attributes) : UserInterface;
}