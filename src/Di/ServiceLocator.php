<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 17:48
 */

namespace Mindy\Di;

use Mindy\Creator\Creator;

/**
 * Class ServiceManager
 * @package Mindy\Di
 */
class ServiceLocator implements ServiceLocatorInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * ServiceManager constructor.
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
        $this->instances = [];
    }

    /**
     * @param $alias
     * @param null $definition
     * @return $this
     * @throws \Exception
     */
    public function add($alias, $definition = null)
    {
        if ($this->has($alias)) {
            throw new \Exception('Component with ' . $alias . ' alias already registered');
        }

        $this->definitions[$alias] = $definition;
        return $this;
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function get($alias)
    {
        if (isset($this->instances[$alias])) {
            return $this->instances[$alias];
        }

        if (isset($this->definitions[$alias])) {
            $definition = $this->definitions[$alias];
            if (is_object($definition) && !$definition instanceof \Closure) {
                $this->instances[$alias] = $definition;
            } else {
                $this->instances[$alias] = Creator::createObject($definition);
            }
            return $this->instances[$alias];
        }

        return null;
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
        return isset($this->instances[$interface]) || isset($this->definitions[$interface]);
    }
}