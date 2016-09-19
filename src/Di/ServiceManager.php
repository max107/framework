<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 17:48
 */

namespace Mindy\Di;

use Mindy\Helper\Creator;

/**
 * Class ServiceManager
 * @package Mindy\Di
 */
class ServiceManager
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
     * @return mixed
     */
    public function get($alias)
    {
        if (isset($this->instances[$alias])) {
            return $this->instances[$alias];
        }

        if (isset($this->definitions[$alias])) {
            $definition = $this->definitions[$alias];
            if ($definition instanceof \Closure) {
                $this->instances[$alias] = $definition();
            } else {
                if (is_string($definition)) {
                    $this->instances[$alias] = new $definition;
                } else {
                    $className = $definition['class'];
                    unset($definition['class']);
                    $this->instances[$alias] = (new \ReflectionClass($className))->newInstanceArgs($definition);
                }
            }
        }

        return $this->instances[$alias];
    }
}