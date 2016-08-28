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
 * Class DjangoPasswordHasher
 * @package Mindy\Auth\PasswordHasher
 */
class DjangoPasswordHasher implements IPasswordHasher
{
    /**
     * @return string random
     */
    public function generateSalt() : string
    {
        return Security::generateRandomString(5, true);
    }

    /**
     * @param $password string
     * @return string
     */
    public function hashPassword(string $password) : string
    {
        $algorithm = "pbkdf2_sha256";
        $iterations = 10000;

        $newSalt = mcrypt_create_iv(6, MCRYPT_DEV_URANDOM);
        $newSalt = base64_encode($newSalt);

        $hash = hash_pbkdf2("SHA256", $password, $newSalt, $iterations, 0, true);
        return $algorithm . "$" . $iterations . "$" . $newSalt . "$" . base64_encode($hash);
    }

    /**
     * @param $password string
     * @param $hash string
     * @return bool
     */
    public function verifyPassword(string $password, string $hash) : bool
    {
        list($alhorithm, $iterations, $salt, $oldHash) = explode("$", $hash);
        $hash = hash_pbkdf2("SHA256", $password, $salt, $iterations, 0, true);
        return base64_encode($hash) == $oldHash;
    }
}
