<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 13:03
 */

declare(strict_types = 1);

namespace Mindy\Auth;

/**
 * Interface IUser
 * @package Mindy\Auth
 * @property int|string $id
 * @property int|string $pk
 * @property string $username
 * @property string $password
 * @property string $hash_type
 * @property array $groups
 * @property bool $is_superuser
 * @property bool $is_active
 */
interface UserInterface
{
    /**
     * @return bool
     */
    public function isGuest() : bool;

    /**
     * @return array
     */
    public function getSafeAttributes() : array;

    /**
     * @param array $attributes
     * @return mixed
     */
    public static function create(array $attributes) : UserInterface;
}