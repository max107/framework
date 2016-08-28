<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/12/14 21:03
 */

namespace Mindy\Auth\PasswordHasher;

/**
 * @deprecated deprecated. Don't use this hasher.
 * Class MD5PasswordHasher
 * @package Mindy\Auth\PasswordHasher
 */
class MD5PasswordHasher implements IPasswordHasher
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
        return md5($password);
    }

    /**
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash) : bool
    {
        return md5($password) == $hash;
    }
}