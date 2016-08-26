<?php

declare(strict_types = 1);

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/06/14.06.2014 17:22
 */

namespace Mindy\Base;

use function GuzzleHttp\Psr7\stream_for;
use Mindy\Console\ConsoleCommandRunner;
use Mindy\Controller\Action\ClosureAction;
use Mindy\Di\ServiceLocator;
use Mindy\Exception\Exception;
use Mindy\Exception\HttpException;
use Mindy\Helper\Alias;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @property string $id The unique identifier for the application.
 * @property string $basePath The root directory of the application. Defaults to 'protected'.
 * @property string $runtimePath The directory that stores runtime files. Defaults to 'protected/runtime'.
 * @property string $extensionPath The directory that contains all extensions. Defaults to the 'extensions' directory under 'protected'.
 * @property string $timeZone The time zone used by this application.
 * @property \Mindy\Event\EventManager $signal The event system component.
 * @property \Mindy\ErrorHandler\ErrorHandler $errorHandler The error handler application component.
 * @property \Mindy\Security\Security $securityManager The security manager application component.
 * @property \Mindy\Http\Http $request The request component.
 * @property \Mindy\Router\UrlManager $urlManager The URL manager component.
 * @property \Mindy\Controller\BaseController $controller The currently active controller. Null is returned in this base class.
 * @property string $baseUrl The relative URL for the application.
 * @property string $homeUrl The homepage URL.
 */
class Application extends BaseApplication
{
    use Configurator;
    use Accessors;

    /**
     * @var array
     */
    public $managers = [];
    /**
     * @var array
     */
    public $admins = [];
    /**
     * @var array
     */
    public $locale = [];
    /**
     * @var array the IDs of the application components that should be preloaded.
     */
    public $preload = [];
    /**
     * @var array
     */
    public $commandMap = [];
    /**
     * @var string
     */
    private $_homeUrl;
    /**
     * @var ConsoleCommandRunner
     */
    private $_consoleRunner;
    /**
     * @var ServiceLocator
     */
    private $_componentLocator;
    /**
     * @var ServiceLocator
     */
    private $_moduleLocator;

    /**
     * Constructor.
     * @param array|string $config application configuration.
     * If a string, it is treated as the path of the file that contains the configuration;
     * If an array, it is the actual configuration information.
     * Please make sure you specify the {@link getBasePath basePath} property in the configuration,
     * which should point to the directory containing all application logic, template and data.
     * If not, the directory will be defaulted to 'protected'.
     * @throws \Mindy\Exception\Exception
     */
    public function __construct($config = null)
    {
        Mindy::setApplication($this);

        $config = $this->fetchConfig($config);

        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new Exception('Unknown basePath');
        }

        if (!is_array($config)) {
            throw new Exception('Unknown config type');
        }

        $this->initAliases($config);
        $this->registerCoreComponents();
        $this->preinit();
        $this->configure($config);
        $this->preloadComponents();

        /**
         * Raise preConfigure method
         * on every iterable module
         */
        $this->init();
    }

    /**
     * return $this;
     */
    protected function registerCoreComponents()
    {
        foreach ($this->getCoreComponents() as $id => $config) {
            $this->setComponent($id, $config);
        }
    }

    /**
     * @param array $components
     * @return $this
     */
    public function setComponents(array $components) : self
    {
        $this->getComponentLocator()->setComponents($components);
        return $this;
    }

    /**
     * @param array $modules
     * @return $this|Application
     */
    public function setModules(array $modules) : self
    {
        $modulesDefinitions = [];
        foreach ($modules as $module => $config) {
            if (is_numeric($module) && is_string($config)) {
                $module = $config;
                $className = $this->getDefaultModuleClassNamespace($config);
                $config = ['class' => $className];
            } else if (is_array($config)) {
                if (isset($config['class'])) {
                    $className = $config['class'];
                } else {
                    $className = $this->getDefaultModuleClassNamespace($module);
                    $config['class'] = $className;
                }
            } else {
                throw new RuntimeException('Unknown module config format');
            }

            $path = Alias::get('modules') . DIRECTORY_SEPARATOR . ucfirst($module);
            Alias::set(ucfirst($module), $path);
            $modulesDefinitions[ucfirst($module)] = function () use ($className, $module, $config) {
                return Creator::createObject($config, $module);
            };
            call_user_func([$className, 'preConfigure']);
        }

        $this->getModuleLocator()->setComponents($modulesDefinitions);
        return $this;
    }

    protected function getComponentLocator() : ServiceLocator
    {
        if ($this->_componentLocator === null) {
            $this->_componentLocator = new ServiceLocator();
        }
        return $this->_componentLocator;
    }

    protected function getModuleLocator() : ServiceLocator
    {
        if ($this->_moduleLocator === null) {
            $this->_moduleLocator = new ServiceLocator();
        }
        return $this->_moduleLocator;
    }

    /**
     * Init system aliases
     *
     * Defines the root aliases.
     * @param array $mappings list of aliases to be defined. The array keys are root aliases,
     * while the array values are paths or aliases corresponding to the root aliases.
     * For example,
     * <pre>
     * array(
     *    'alias'=>'absolute/path'
     * )
     * </pre>
     *
     * @param array $config
     * @throws Exception
     */
    protected function initAliases($config)
    {
        Alias::set('App', $this->getBasePath());
        Alias::set('app', $this->getBasePath());
        Alias::set('application', $this->getBasePath());

        Alias::set('Modules', $this->getModulePath());

        if (isset($config['webPath'])) {
            $path = realpath($config['webPath']);
            if (!is_dir($path)) {
                throw new Exception("Incorrent web path " . $config['webPath']);
            }
            Alias::set('www', $path);
            unset($config['webPath']);
        } else {
            Alias::set('www', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
        }

        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $name => $alias) {
                if (($path = Alias::get($alias)) !== false) {
                    Alias::set($name, $path);
                } else {
                    Alias::set($name, $alias);
                }
            }
            unset($config['aliases']);
        }
    }

    /**
     * @param $name
     * @return string module namespace
     */
    protected function getDefaultModuleClassNamespace(string $name) : string
    {
        return '\\Modules\\' . ucfirst($name) . '\\' . ucfirst($name) . 'Module';
    }

    /**
     * Preinitializes the module.
     * This method is called at the beginning of the module constructor.
     * You may override this method to do some customized preinitialization work.
     * Note that at this moment, the module is not configured yet.
     * @see init
     */
    protected function preinit()
    {
    }

    /**
     * Loads static application components.
     */
    protected function preloadComponents()
    {
        if (php_sapi_name() !== 'cli') {
            $this->preload[] = 'request';
        }

        foreach ($this->preload as $id) {
            $this->getComponent($id);
        }
    }

    /**
     * Retrieves the named application module.
     * The module has to be declared in {@link modules}. A new instance will be created
     * when calling this method with the given ID for the first time.
     * @param string $id application module ID (case-sensitive)
     * @return Module the module instance, null if the module is disabled or does not exist.
     */
    public function getModule(string $id) : Module
    {
        return $this->getModuleLocator()->get($id);
    }

    /**
     * Returns a value indicating whether the specified module is installed.
     * @param string $id the module ID
     * @return boolean whether the specified module is installed.
     * @since 1.1.2
     */
    public function hasModule($id)
    {
        return $this->getModuleLocator()->has($id);
    }

    /**
     * Returns the configuration of the currently installed modules.
     * @return array the configuration of the currently installed modules (module ID => configuration)
     */
    public function getModules()
    {
        return $this->getModuleLocator()->getComponents();
    }

    public function __call($name, $args)
    {
        if (empty($args) && strpos($name, 'get') === 0) {
            $tmp = lcfirst(str_replace('get', '', $name));

            if ($this->hasComponent($tmp)) {
                return $this->getComponent($tmp);
            }
        }

        return $this->__callInternal($name, $args);
    }

    public function __get($name)
    {
        if ($this->getComponentLocator()->has($name)) {
            return $this->getComponentLocator()->get($name);
        } else {
            $getter = 'get' . $name;
            if (method_exists($this, $getter)) {
                return $this->$getter();
            } elseif (method_exists($this, 'set' . $name)) {
                throw new Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
            } else {
                throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
            }
        }
    }

    protected function runInternal()
    {
        if (php_sapi_name() === 'cli') {
            $this->runCli();
        } else {
            $this->runWeb();
        }
    }

    /**
     * @return ConsoleCommandRunner
     */
    public function getCommandRunner()
    {
        if ($this->_consoleRunner === null) {
            $runner = new ConsoleCommandRunner();
            $runner->setCommandMap($this->commandMap);

            $applicationCommandPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Commands';
            if ($commandPath = realpath($applicationCommandPath)) {
                $runner->addCommands($commandPath, 'base');
            }

            if ($env = @getenv('CONSOLE_COMMANDS')) {
                $runner->addCommands($env, 'base');
            }

            foreach ($this->getModules() as $name => $settings) {
                $modulePath = Alias::get("Modules." . $name . ".Commands");
                if ($modulePath) {
                    $runner->addCommands($modulePath, $name);
                }
            }
            $this->_consoleRunner = $runner;
        }
        return $this->_consoleRunner;
    }

    protected function runCli()
    {
        // fix for fcgi
        defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
        if (!isset($_SERVER['argv'])) {
            echo 'This script must be run from the command line.' . PHP_EOL;
            $this->end(1);
        }
        $this->getCommandRunner()->run($_SERVER['argv']);
    }

    protected function runWeb()
    {
        ob_start();
        $output = $this->parseRoute();
        $html = ob_get_clean();
        if ($output instanceof ResponseInterface) {
            $this->request->send($output);
        } else {
            $this->request->html($html);
        }
    }

    /**
     * Returns the relative URL for the application.
     * This is a shortcut method to {@link CHttpRequest::getBaseUrl()}.
     * @param boolean $absolute whether to return an absolute URL. Defaults to false, meaning returning a relative one.
     * @return string the relative URL for the application
     * @see CHttpRequest::getBaseUrl()
     */
    public function getBaseUrl($absolute = false)
    {
        return $this->request->http->getBaseUrl($absolute);
    }

    /**
     * @return string the homepage URL
     */
    public function getHomeUrl()
    {
        return $this->_homeUrl === null ? '/' : $this->_homeUrl;
    }

    /**
     * @param string $value the homepage URL
     */
    public function setHomeUrl($value)
    {
        $this->_homeUrl = $value;
    }

    /**
     * Registers the core application components.
     */
    protected function getCoreComponents()
    {
        return [
            'security' => [
                'class' => '\Mindy\Security\Security',
            ],
            'urlManager' => [
                'class' => '\Mindy\Router\UrlManager'
            ],
            'request' => [
                'class' => '\Mindy\Http\Http',
            ],
            'signal' => [
                'class' => '\Mindy\Event\EventManager',
            ],
        ];
    }

    public function parseRoute()
    {
        $request = $this->request->getRequest();
        $response = $this->urlManager->dispatch($request->getMethod(), $request->getRequestTarget());
        if ($response === false) {
            throw new HttpException(404);
        }
        return $response;
    }

    /**
     * @throws \Mindy\Exception\Exception
     * @return \Modules\User\Models\User instance the user session information
     */
    public function getUser()
    {
        return $this->auth->getModel();
    }

    //////////////////
    // DEPRECATED
    //////////////////

    /**
     * @param $id
     * @return object|null
     */
    public function getComponent($id)
    {
        return $this->getComponentLocator()->get($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasComponent($id)
    {
        return $this->getComponentLocator()->has($id);
    }

    /**
     * @param $id
     * @param $config
     * @void
     */
    public function setComponent($id, $config)
    {
        $this->getComponentLocator()->set($id, $config);
    }

    /**
     * @param bool $definitions
     * @return array
     */
    public function getComponents($definitions = true)
    {
        return $this->getComponentLocator()->getComponents($definitions);
    }
}
