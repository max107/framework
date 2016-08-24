<?php

namespace Mindy\Di;

use Closure;
use Exception;
use Mindy\Helper\Creator;

/**
 * Class ServiceLocator
 * @package Mindy\Di
 */
class ServiceLocator
{
    /**
     * @var array shared component instances indexed by their IDs
     */
    private $_components = [];
    /**
     * @var array component definitions indexed by their IDs
     */
    private $_definitions = [];

    /**
     * ServiceLocator constructor.
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        $this->_definitions = $definitions;
    }

    /**
     * Getter magic method.
     * This method is overridden to support accessing components like reading properties.
     * @param string $name component or property name
     * @return mixed the named property value
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named component is loaded.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     */
    public function __isset($name)
    {
        return $this->has($name, true);
    }

    /**
     * Returns a value indicating whether the locator has the specified component definition or has instantiated the component.
     * This method may return different results depending on the value of `$checkInstance`.
     *
     * - If `$checkInstance` is false (default), the method will return a value indicating whether the locator has the specified
     *   component definition.
     * - If `$checkInstance` is true, the method will return a value indicating whether the locator has
     *   instantiated the specified component.
     *
     * @param string $id component ID (e.g. `db`).
     * @param boolean $checkInstance whether the method should check if the component is shared and instantiated.
     * @return boolean whether the locator has the specified component definition or has instantiated the component.
     * @see set()
     */
    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    /**
     * Returns the component instance with the specified ID.
     *
     * @param string $id component ID (e.g. `db`).
     * @param boolean $throwException whether to throw an exception if `$id` is not registered with the locator before.
     * @return object|null the component of the specified ID. If `$throwException` is false and `$id`
     * is not registered before, null will be returned.
     * @throws Exception if `$id` refers to a nonexistent component ID
     * @see has()
     * @see set()
     */
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_callable($definition)) {
                return $this->_components[$id] = $definition();
            } else if (is_object($definition)) {
                return $this->_components[$id] = $definition;
            } else {
                if (isset($definition[0]) && isset($definition['class'])) {
                    $class = $definition['class'];
                    unset($definition['class']);
                    return $this->_components[$id] = call_user_func_array([Creator::class, 'createObject'], array_merge([$class], $definition));
                } else {
                    return $this->_components[$id] = Creator::createObject($definition);
                }
            }
        } elseif ($throwException) {
            throw new Exception("Unknown component ID: $id");
        } else {
            return null;
        }
    }

    /**
     * Registers a component definition with this locator.
     *
     * For example,
     *
     * ```php
     * // a class name
     * $locator->set('cache', 'yii\caching\FileCache');
     *
     * // a configuration array
     * $locator->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // an anonymous function
     * $locator->set('cache', function ($params) {
     *     return new \yii\caching\FileCache;
     * });
     *
     * // an instance
     * $locator->set('cache', new \yii\caching\FileCache);
     * ```
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * @param string $id component ID (e.g. `db`).
     * @param mixed $definition the component definition to be registered with this locator.
     * It can be one of the followings:
     *
     * - a class name
     * - a configuration array: the array contains name-value pairs that will be used to
     *   initialize the property values of the newly created object when [[get()]] is called.
     *   The `class` element is required and stands for the the class of the object to be created.
     * - a PHP callable: either an anonymous function or an array representing a class method (e.g. `['Foo', 'bar']`).
     *   The callable will be called by [[get()]] to return an object associated with the specified component ID.
     * - an object: When [[get()]] is called, this object will be returned.
     *
     * @throws Exception if the definition is an invalid configuration array
     */
    public function set($id, $definition)
    {
        if ($definition === null) {
            unset($this->_components[$id], $this->_definitions[$id]);
            return;
        }

        unset($this->_components[$id]);

        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new Exception("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new Exception("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    /**
     * Removes the component from the locator.
     * @param string $id the component ID
     */
    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    /**
     * Returns the list of the component definitions or the loaded component instances.
     * @param boolean $returnDefinitions whether to return component definitions instead of the loaded component instances.
     * @return array the list of the component definitions or the loaded component instances (ID => definition or instance).
     */
    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

    /**
     * Registers a set of component definitions in this locator.
     *
     * This is the bulk version of [[set()]]. The parameter should be an array
     * whose keys are component IDs and values the corresponding component definitions.
     *
     * For more details on how to specify component IDs and definitions, please refer to [[set()]].
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * The following is an example for registering two component definitions:
     *
     * ```php
     * [
     *     'db' => [
     *         'class' => 'yii\db\Connection',
     *         'dsn' => 'sqlite:path/to/file.db',
     *     ],
     *     'cache' => [
     *         'class' => 'yii\caching\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     *
     * @param array $components component definitions or instances
     */
    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }
}
