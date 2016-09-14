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

use League\Container\ContainerInterface;
use Mindy\Console\ConsoleApplication;
use Mindy\Di\ModuleContainer;
use Mindy\Di\Container;
use Mindy\Event\EventManager;
use Mindy\Exception\Exception;
use Mindy\Exception\HttpException;
use Mindy\Helper\Alias;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Http\Http;
use Mindy\Router\UrlManager;
use Mindy\Security\Security;
use Psr\Http\Message\ResponseInterface;

/**
 * @property string $id The unique identifier for the application.
 * @property string $basePath The root directory of the application. Defaults to 'protected'.
 * @property string $runtimePath The directory that stores runtime files. Defaults to 'protected/runtime'.
 * @property string $extensionPath The directory that contains all extensions. Defaults to the 'extensions' directory under 'protected'.
 * @property string $timeZone The time zone used by this application.
 * @property \Mindy\Event\EventManager $signal The event system component.
 * @property \Mindy\ErrorHandler\ErrorHandler $errorHandler The error handler application component.
 * @property \Mindy\Security\Security $securityManager The security manager application component.
 * @property \Mindy\Http\Http $http The request component.
 * @property \Mindy\Auth\IUser $user The user component.
 * @property \Mindy\Template\Renderer $template The template component.
 * @property \Mindy\Finder\Finder $finder The template finder component.
 * @property \Mindy\Auth\AuthProvider $auth The auth component.
 * @property \Mindy\Router\UrlManager $urlManager The URL manager component.
 * @property \Mindy\Storage\Storage $storage The storage component.
 * @property \Mindy\Controller\BaseController $controller The currently active controller. Null is returned in this base class.
 * @property string $baseUrl The relative URL for the application.
 */
class Application extends BaseApplication implements ModulesAwareInterface
{
    use Configurator;
    use Accessors;
    use ModulesAwareTrait;
    use DeprecatedMethodsTrait;

    /**
     * @var array
     */
    public $managers = [];
    /**
     * @var array
     */
    public $admins = [];
    /**
     * @var string The homepage URL.
     */
    public $homeUrl;
    /**
     * @var string
     */
    public $webPath;

    /**
     * Constructor.
     * @param array|string $config application configuration.
     * If a string, it is treated as the path of the file that contains the configuration;
     * If an array, it is the actual configuration information.
     * Please make sure you specify the {@link getBasePath basePath} property in the configuration,
     * which should point to the directory containing all application logic, template and data.
     * If not, the directory will be defaulted to 'protected'.
     * @param ContainerInterface $container
     * @throws Exception
     * @throws \Exception
     */
    public function __construct($config = null, ContainerInterface $container = null)
    {
        Mindy::setApplication($this);

        $config = $this->fetchConfig($config);
        if (!is_array($config)) {
            throw new Exception('Unknown config type');
        }

        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new Exception('Unknown basePath');
        }

        if (isset($config['webPath'])) {
            $this->setWebPath($config['webPath']);
            unset($config['webPath']);
        }

        $this->initAliases($config);
        if (isset($config['aliases'])) {
            unset($config['aliases']);
        }

        $modules = [];
        if (isset($config['modules'])) {
            $modules = $config['modules'];
            unset($config['modules']);
        }

        $components = [];
        if (isset($config['components'])) {
            $components = $config['components'];
            unset($config['components']);
        }

        $this->configure($config);

        if ($container === null) {
            $container = new Container;
        }
        if (!empty($components)) {
            $componentServiceProvider = new LegacyComponentsServiceProvider($components);
            $container->addServiceProvider($componentServiceProvider);
        }
        $container = $this->prepareContainer($container);

        $this->setContainer($container);
        $this->initModules($modules);

        if (isset($config['modules'])) {
            unset($config['modules']);
        }

        $this->init();
    }

    /**
     * @param ContainerInterface $container
     * @return ContainerInterface
     */
    protected function prepareContainer(ContainerInterface $container)
    {
        if ($container->has('security') === false) {
            $container->add('security', Security::class);
        }
        if ($container->has('urlManager') === false) {
            $container->add('urlManager', UrlManager::class);
        }
        if ($container->has('http') === false) {
            $container->add('http', Http::class);
        }
        if ($container->has('signal') === false) {
            $container->add('signal', EventManager::class);
        }
        return $container;
    }

    /**
     * @param $category
     * @param $message
     * @param array $params
     * @param null $language
     * @return mixed
     */
    public function t($category, $message, $params = [], $language = null) : string
    {
        $container = $this->getContainer();
        if ($container && $container->has('locale')) {
            return $container->get('locale')->t($category, $message, $params, $language);
        } else {
            return strtr($message, $params);
        }
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
        $aliases = [];
        if (isset($config['aliases'])) {
            $aliases = $config['aliases'];
            unset($config['aliases']);
        }

        Alias::fromMap(array_merge([
            'App' => $this->getBasePath(),
            'app' => $this->getBasePath(),
            'application' => $this->getBasePath(),
            'Modules' => $this->getModulePath(),
            'www' => $this->getWebPath()
        ], $aliases));
    }

    /**
     * @param string $webPath
     * @throws Exception
     */
    public function setWebPath(string $webPath)
    {
        $path = realpath($webPath);
        if (!is_dir($path)) {
            throw new Exception("Incorrent web path " . $webPath);
        }
        $this->webPath = $path;
    }

    /**
     * @return string
     */
    public function getWebPath()
    {
        if ($this->webPath === null) {
            $this->webPath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        }

        return $this->webPath;
    }

    /**
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if ($this->getContainer()->has($name)) {
            return $this->getContainer()->get($name);
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

    /**
     * Start application
     */
    protected function runInternal()
    {
        if (php_sapi_name() === 'cli') {
            $this->runCli();
        } else {
            $this->runWeb();
        }
    }

    /**
     * @throws \Exception
     */
    protected function runCli()
    {
        $consoleApplication = new ConsoleApplication($this->name, self::getVersion());

        // Preload all modules
        $modules = [];
        foreach ($this->getModules() as $id => $module) {
            $modules[$id] = $this->getModuleLocator()->get($id);
        }

        $modulesCommands = $consoleApplication->findModulesCommands($modules);
        $consoleApplication->addCommands($modulesCommands);

        if ($envPath = @getenv('CONSOLE_COMMANDS')) {
            $envCommands = $consoleApplication->findCommands($envPath);
            $consoleApplication->addCommands($envCommands);
        }

        $consoleApplication->run();
    }

    protected function runWeb()
    {
        ob_start();
        $output = $this->parseRoute();
        $html = ob_get_clean();
        if (!empty($html)) {
            $response = $html instanceof ResponseInterface ? $html : $this->http->html($html);
        } else {
            $response = $output instanceof ResponseInterface ? $output : $this->http->html($output);
        }
        $this->http->send($response);
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

    public function parseRoute()
    {
        $request = $this->http->getRequest();
        $response = $this->urlManager->dispatch($request->getMethod(), $request->getRequestTarget());
        if ($response === false) {
            throw new HttpException(404, 'Page not found');
        }
        return $response;
    }

    /**
     * @throws \Mindy\Exception\Exception
     * @return \Mindy\Auth\IUser instance the user session information
     */
    public function getUser()
    {
        if ($this->getContainer()->has('auth')) {
            return $this->auth->getUser();
        }
        return null;
    }

    /**
     * Create di container for modules
     * @param array $modules
     */
    protected function initModules(array $modules)
    {
        $moduleContainer = new ModuleContainer;
        $moduleContainer->addServiceProvider(new ModuleServiceProvider($modules, $this->getModulePath()));
        $this->setModulesContainer($moduleContainer);
    }
}
