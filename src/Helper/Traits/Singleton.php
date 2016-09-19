<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 18/07/14.07.2014 20:59
 */

namespace Mindy\Helper\Traits;

use Mindy\Creator\Creator;
use ReflectionClass;

/**
 * Class Singleton
 * @package Mindy\Helper
 */
trait Singleton
{
    protected static $instance;

    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = new ReflectionClass(__CLASS__);
            self::$instance = $class->newInstanceArgs(func_get_args());
        }

        return self::$instance;
    }

    final private function __clone()
    {
    }

    final private function __wakeup()
    {
    }

    /**
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * Constructor.
     * The default implementation does two things:
     *
     * - Initializes the object with the given configuration `$config`.
     * - Call [[init()]].
     *
     * If this method is overridden in a child class, it is recommended that
     *
     * - the last parameter of the constructor is a configuration array, like `$config` here.
     * - call the parent implementation at the end of the constructor.
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    final public function __construct($config = [])
    {
        $this->configure($config);
        $this->init();
    }

    protected function configure($config = [])
    {
        if (!empty($config)) {
            Creator::configure($this, $config);
        }
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
    }
}
