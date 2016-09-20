<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 20:33
 */

namespace Mindy\Di;

interface ModuleManagerInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return ModuleManagerInterface
     */
    public function loadModules(ServiceLocatorInterface $serviceLocator);

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

    /**
     * @return mixed
     */
    public function all();
}