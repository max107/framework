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

use Mindy\Helper\Password;

/**
 * Class MindyPasswordHasher
 * @package Mindy\Auth\PasswordHasher
 */
class MindyPasswordHasher implements IPasswordHasher
{
    /**
     * @return string random
     */
    public function generateSalt() : string
    {
        return Password::generateSalt();
    }

    /**
     * @param $password string
     * @return string
     */
    public function hashPassword(string $password) : string
    {
        return Password::hashPassword($password);
    }

    /**
     * @param string $password
     * @param string $hash
     * @return bool|string
     */
    public function verifyPassword(string $password, string $hash) : bool
    {
        return Password::verifyPassword($password, $hash);
    }
}