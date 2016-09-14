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
 * @date 16/10/14.10.2014 18:57
 */

namespace Mindy\Base;

use League\Container\Container;
use League\Container\ContainerAwareTrait;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use function Mindy\app;
use Mindy\Helper\Collection;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use ReflectionClass;

/**
 * BaseModule is the base class for module and application classes.
 * @property string $id The module ID.
 * @property string $basePath The root directory of the module. Defaults to the directory containing the module class.
 * @property Collection $params The list of user-defined parameters.
 * @property string $modulePath The directory that contains the application modules. Defaults to the 'modules' subdirectory of {@link basePath}.
 * @property array $modules The configuration of the currently installed modules (module ID => configuration).
 * @property array $components The application components (indexed by their IDs).
 * @property array $import List of aliases to be imported.
 * @property array $aliases List of aliases to be defined. The array keys are root aliases,
 */
abstract class BaseModule implements ModuleInterface, BootableServiceProviderInterface
{
    use Configurator;
    use Accessors;
    use ContainerAwareTrait;
    use DeprecatedMethodsTrait;

    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var array
     */
    protected $provides = [];

    /**
     * BaseModule constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setContainer(new Container);
        $this->configure($config);
        $this->init();
    }

    /**
     * Method will be invoked on registration of a service provider implementing
     * this interface. Provides ability for eager loading of Service Providers.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Returns a boolean if checking whether this provider provides a specific
     * service or returns an array of provided services if no argument passed.
     *
     * @param  string $service
     * @return boolean|array
     */
    public function provides($service = null)
    {
        return array_key_exists($service, $this->provides);
    }

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Getter magic method.
     * This method is overridden to support accessing application components
     * like reading module properties.
     * @param string $name application component or property name
     * @return mixed the named property value
     */
    public function __get($name)
    {
        if ($this->getContainer()->has($name)) {
            return $this->getContainer()->get($name);
        } else {
            return $this->__getInternal($name);
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking
     * if the named application component is loaded.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     */
    public function __isset($name)
    {
        if ($this->getContainer()->has($name)) {
            return true;
        } else {
            return $this->__issetInternal($name);
        }
    }

    /**
     * Configure module or application before call constructor
     */
    public static function preConfigure()
    {

    }

    /**
     * Returns the module ID.
     * @return string the module ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::t(ucfirst($this->getId()));
    }

    /**
     * Returns the root directory of the module.
     * @return string the root directory of the module. Defaults to the directory containing the module class.
     */
    public function getBasePath()
    {
        if ($this->basePath === null) {
            $class = new ReflectionClass(get_class($this));
            $this->basePath = dirname($class->getFileName());
        }
        return $this->basePath;
    }

    /**
     * @param $str
     * @param array $params
     * @param string $dic
     * @return string
     */
    public static function t($str, $params = [], $dic = 'main')
    {
        return app()->t(get_called_class() . "." . $dic, $str, $params);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return app()->getParam($this->getId());
    }
}
