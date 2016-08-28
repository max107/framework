<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/12/14 21:09
 */

namespace Mindy\Auth\PasswordHasher;

use Mindy\Helper\Security;

/**
 * Class BitrixPasswordHasher
 * @package Mindy\Auth\PasswordHasher
 */
class BitrixPasswordHasher implements IPasswordHasher
{
    /**
     * @return string random
     */
    public function generateSalt() : string
    {
        return Security::generateRandomString(8, true);
    }

    /**
     * @param $password string
     * @return string
     */
    public function hashPassword(string $password) : string
    {
        $salt = $this->generateSalt();
        return $salt . md5($salt . $password);
    }

    /**
     * @param $password string
     * @param $hash string
     * @return bool
     */
    public function verifyPassword(string $password, string $hash) : bool
    {
        $salt = substr($hash, 0, 8);
        return $hash == $salt . md5($salt . $password);
    }
}
