<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:08
 */

namespace Mindy\Auth\UserProvider;

/**
 * Interface UserProviderInterface
 * @package Mindy\Auth\UserProvider
 */
interface UserProviderInterface
{
    /**
     * @param array $attributes
     * @return null|\Mindy\Auth\UserInterface|\Mindy\Orm\ModelInterface
     */
    public function get(array $attributes);
}