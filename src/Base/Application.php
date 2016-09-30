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

use Mindy\Console\ConsoleApplication;
use Mindy\Di\ModuleManager;
use Mindy\Di\ServiceLocator;
use Mindy\Event\EventManager;
use Mindy\Exception\Exception;
use Mindy\Exception\HttpException;
use Mindy\Helper\Alias;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Http\Http;
use Mindy\Orm\Migrations\Commands\ExecuteCommand;
use Mindy\Orm\Migrations\Commands\GenerateCommand;
use Mindy\Orm\Migrations\Commands\MigrateCommand;
use Mindy\Orm\Migrations\Commands\ModuleConfigurationHelper;
use Mindy\Orm\Migrations\Commands\StatusCommand;
use Mindy\Orm\Migrations\Commands\VersionCommand;
use Mindy\Router\UrlManager;
use Mindy\Security\Security;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @property string $id The unique identifier for the application.
 * @property string $basePath The root directory of the application. Defaults to 'protected'.
 * @property string $runtimePath The directory that stores runtime files. Defaults to 'protected/runtime'.
   * @property \Mindy\Event\EventManager $signal The event system component.
 * @property \Mindy\Security\Security $securityManager The security manager application component.
 * @property \Mindy\Http\Http $http The request component.
 * @property \Mindy\Auth\UserInterface $user The user component.
 * @property \Mindy\Template\Renderer $template The template component.
 * @property \Mindy\Finder\Finder $finder The template finder component.
 * @property \Mindy\Auth\AuthProvider $auth The auth component.
 * @property \Mindy\Router\UrlManager $urlManager The URL manager component.
 * @property \Mindy\Translator\Locale $locale The locale component.
 * @property \Mindy\Storage\Storage $storage The storage component.
 * @property \Doctrine\Common\Cache\CacheProvider $cache The cache component. Default is a doctrine\cache.
 */
class Application extends BaseApplication
{
    use Configurator;
    use Accessors;
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
     * @var string The relative URL for the application.
     */
    public $baseUrl = '/';
    /**
     * @var string
     */
    public $homeUrl = '/';
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
     * @throws Exception
     * @throws \Exception
     */
    public function __construct($config = null)
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

        if (isset($config['baseUrl'])) {
            $this->setBaseUrl($config['baseUrl']);
            unset($config['baseUrl']);
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

        $serviceLocator = new ServiceLocator($components);
        $this->setServiceLocator($this->prepareServiceLocator($serviceLocator));

        $moduleManager = new ModuleManager($modules);
        $moduleManager->loadModules($this->getServiceLocator());
        $this->setModuleManager($moduleManager);

        $this->init();
    }

    /**
     * @param ServiceLocator $container
     * @return ServiceLocator
     */
    protected function prepareServiceLocator(ServiceLocator $serviceLocator)
    {
        if ($serviceLocator->has('security') === false) {
            $serviceLocator->add('security', Security::class);
        }
        if ($serviceLocator->has('urlManager') === false) {
            $serviceLocator->add('urlManager', UrlManager::class);
        }
        if ($serviceLocator->has('http') === false) {
            $serviceLocator->add('http', Http::class);
        }
        if ($serviceLocator->has('signal') === false) {
            $serviceLocator->add('signal', EventManager::class);
        }
        return $serviceLocator;
    }

    /**
     * @param $message
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function t($domain, $message, array $parameters = [], $locale = null) : string
    {
        $container = $this->getServiceLocator();
        if ($container && $container->has('locale')) {
            return $container->get('locale')->t($domain, $message, $parameters, $locale);
        } else {
            return strtr($message, $parameters);
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
        if ($this->getServiceLocator()->has($name)) {
            return $this->getServiceLocator()->get($name);
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

        $helpers = $consoleApplication->getDefaultHelpers();
        if ($this->getServiceLocator()->has('db')) {
            $helpers = array_merge($helpers, [
                'configuration' => new ModuleConfigurationHelper($this->db->getConnection()),
            ]);
        }
        $helperSet = new HelperSet($helpers);

        $consoleApplication->setHelperSet($helperSet);

        if ($this->getServiceLocator()->has('db')) {
            $consoleApplication->addCommands([
                new ExecuteCommand(),
                new GenerateCommand(),
                new MigrateCommand(),
                new StatusCommand(),
                new VersionCommand()
            ]);
        }

        $modulesCommands = $consoleApplication->findModulesCommands($this->getModules());
        $consoleApplication->addCommands($modulesCommands);

        if ($envPath = @getenv('CONSOLE_COMMANDS')) {
            $envCommands = $consoleApplication->findCommands($envPath);
            $consoleApplication->addCommands($envCommands);
        }

        $consoleApplication->run();
    }

    /**
     * @void
     */
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
        if ($absolute) {
            return $this->http->getRequest()->getHeaderLine('Host') . $this->baseUrl;
        } else {
            return $this->baseUrl;
        }
    }

    /**
     * @param string $url
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
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
     * @return \Mindy\Auth\UserInterface instance the user session information
     */
    public function getUser()
    {
        if ($this->getServiceLocator()->has('auth')) {
            return $this->auth->getUser();
        }
        return null;
    }
}
