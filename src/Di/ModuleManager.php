<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 20:32
 */

namespace Mindy\Di;

use Mindy\Base\ModuleInterface;
use Mindy\Creator\Creator;
use Mindy\Helper\Alias;

class ModuleManager implements ModuleManagerInterface
{
    /**
     * @var array
     */
    protected $definitions = [];
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * ModuleManager constructor.
     * @param array $modules
     */
    public function __construct(array $modules = [])
    {
        $this->definitions = $modules;
        $this->instances = [];
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return $this
     */
    public function loadModules(ServiceLocatorInterface $serviceLocator)
    {
        foreach ($this->definitions as $id => $config) {
            if (is_string($config)) {
                $config = ['class' => $config, 'id' => $id];
            } else if (is_array($config)) {
                $config['id'] = $id;
            }
            $instance = Creator::createObject($config);
            $instance->boot($serviceLocator);

            Alias::set($instance->getId(), $instance->getBasePath());

            $this->instances[$id] = $instance;
        }
        return $this;
    }

    /**
     * Checks if a service is registered.
     *
     * @param string $interface
     *
     * @return bool
     */
    public function has($interface)
    {
        return isset($this->instances[$interface]);
    }

    /**
     * Gets the service registered for the interface.
     *
     * @param string $interface
     * @return mixed
     * @throws \Exception
     */
    public function get($interface)
    {
        if ($this->has($interface)) {
            return $this->instances[$interface];
        }

        throw new \Exception('Unknown module: ' . $interface);
    }

    /**
     * @return array|ModuleInterface[]
     */
    public function all() : array
    {
        return $this->instances;
    }
}