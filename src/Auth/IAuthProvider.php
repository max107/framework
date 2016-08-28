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
interface IAuthProvider
{
    /**
     * @param string $hasher
     * @return IPasswordHasher
     * @throws Exception
     */
    public function getPasswordHasher(string $hasher) : IPasswordHasher;
}