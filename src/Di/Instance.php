<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Mindy\Di;

use Exception;

/**
 * Instance represents a reference to a named object in a dependency injection (DI) container or a service locator.
 *
 * You may use [[get()]] to obtain the actual object referenced by [[id]].
 *
 * Instance is mainly used in two places:
 *
 * - When configuring a dependency injection container, you use Instance to reference a class name, interface name
 *   or alias name. The reference can later be resolved into the actual object by the container.
 * - In classes which use service locator to obtain dependent objects.
 *
 * The following example shows how to configure a DI container with Instance:
 *
 * ```php
 * $container = new \yii\di\Container;
 * $container->set('cache', 'yii\caching\DbCache', Instance::of('db'));
 * $container->set('db', [
 *     'class' => 'yii\db\Connection',
 *     'dsn' => 'sqlite:path/to/file.db',
 * ]);
 * ```
 *
 * And the following example shows how a class retrieves a component from a service locator:
 *
 * ```php
 * class DbCache extends Cache
 * {
 *     public $db = 'db';
 *
 *     public function init()
 *     {
 *         parent::init();
 *         $this->db = Instance::ensure($this->db, 'yii\db\Connection');
 *     }
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 * @package Mindy\Di
 */
class Instance
{
    /**
     * @var string the component ID, class name, interface name or alias name
     */
    public $id;
    /**
     * @var callable
     */
    protected $fetcher;

    /**
     * Constructor.
     * @param string $id the component ID
     * @param callable $fetcher
     */
    protected function __construct($id, callable $fetcher = null)
    {
        $this->id = $id;
        $this->fetcher = $fetcher;
    }

    /**
     * Creates a new Instance object.
     * @param string $id the component ID
     * @param callable $fetcher
     * @return Instance the new Instance object.
     */
    public static function of($id, callable $fetcher = null)
    {
        return new static($id, $fetcher);
    }

    /**
     * Resolves the specified reference into the actual object and makes sure it is of the specified type.
     *
     * The reference may be specified as a string or an Instance object. If the former,
     * it will be treated as a component ID, a class/interface name or an alias, depending on the container type.
     *
     * If you do not specify a container, the method will first try `Mindy::$app` followed by `Mindy::$container`.
     *
     * For example,
     *
     * ```php
     * use yii\db\Connection;
     *
     * // returns Mindy::$app->db
     * $db = Instance::ensure('db', Connection::className());
     * // or
     * $instance = Instance::of('db');
     * $db = Instance::ensure($instance, Connection::className());
     * ```
     *
     * @param object|string|static $reference an object or a reference to the desired object.
     * You may specify a reference in terms of a component ID or an Instance object.
     * @param string $type the class/interface name to be checked. If null, type check will not be performed.
     * @param ServiceLocator|Container $container the container. This will be passed to [[get()]].
     * @return object the object referenced by the Instance, or `$reference` itself if it is an object.
     * @throws Exception if the reference is invalid
     */
    public static function ensure($reference, $type = null, $container = null)
    {
        if ($reference instanceof $type) {
            return $reference;
        } elseif (empty($reference)) {
            throw new Exception('The required component is not specified.');
        }

        if (is_string($reference)) {
            $reference = new static($reference);
        }

        if ($reference instanceof self) {
            $component = $reference->get($container);
            if ($component instanceof $type || $type === null) {
                return $component;
            } else {
                throw new Exception('"' . $reference->id . '" refers to a ' . get_class($component) . " component. $type is expected.");
            }
        }

        $valueType = is_object($reference) ? get_class($reference) : gettype($reference);
        throw new Exception("Invalid data type: $valueType. $type is expected.");
    }

    /**
     * Returns the actual object referenced by this Instance object.
     * @param ServiceLocator|Container $container the container used to locate the referenced object.
     * If null, the method will first try `Mindy::$app` then `Mindy::$container`.
     * @return object the actual object referenced by this Instance object.
     */
    public function get($container = null)
    {
        if ($container) {
            return $container->get($this->id);
        } else if ($fetcher = $this->fetcher) {
            return $fetcher($this->id);
        }
        return null;
    }
}
