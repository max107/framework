<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:37
 */

namespace Mindy\Auth\PasswordHasher;

class NullPasswordHasher implements IPasswordHasher
{
    /**
     * @return string random
     */
    public function generateSalt() : string
    {
        return '';
    }

    /**
     * @param $password string
     * @return string
     */
    public function hashPassword(string $password) : string
    {
        return $password;
    }

    /**
     * @param $password string
     * @param $hash string
     * @return bool
     */
    public function verifyPassword(string $password, string $hash) : bool
    {
        return $password === $hash;
    }
}