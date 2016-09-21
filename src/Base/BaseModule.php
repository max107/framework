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

use function Mindy\app;
use Mindy\Di\ServiceLocatorInterface;
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
abstract class BaseModule implements ModuleInterface
{
    use Configurator;
    use Accessors;

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
        $this->configure($config);
        $this->init();
    }

    /**
     * Method will be invoked on registration of a service provider implementing
     * this interface. Provides ability for eager loading of Service Providers.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function boot(ServiceLocatorInterface $serviceLocator)
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
     * @param null $domain
     * @param $message
     * @param array $parameters
     * @param null $locale
     * @return string
     */
    public static function t($domain, $message, array $parameters = [], $locale = null) : string
    {
        return app()->t($domain, $message, $parameters, $locale);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return app()->getParam($this->getId());
    }
}
