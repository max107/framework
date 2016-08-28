<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 16:06
 */

namespace Mindy\Auth\PasswordHasher;

/**
 * Interface IPasswordHasher
 * @package Mindy\Auth\PasswordHasher
 */
interface IPasswordHasher
{
    /**
     * @return string random
     */
    public function generateSalt() : string;

    /**
     * @param $password string
     * @return string
     */
    public function hashPassword(string $password) : string;

    /**
     * @param $password string
     * @param $hash string
     * @return bool
     */
    public function verifyPassword(string $password, string $hash) : bool;
}