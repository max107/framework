<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 20:30
 */

namespace Mindy\Di;

/**
 * Interface ServiceLocatorInterface
 * @package Mindy\Di
 */
interface ServiceLocatorInterface
{
    /**
     * Add the service definition for the interface.
     *
     * @param $alias
     *
     * @param null $definition
     *
     * @return mixed
     */
    public function add($alias, $definition = null);

    /**
     * Checks if a service is registered.
     *
     * @param string $interface
     *
     * @return bool
     */
    public function has($interface);

    /**
     * Gets the service registered for the interface.
     *
     * @param string $interface
     *
     * @return mixed
     */
    public function get($interface);
}