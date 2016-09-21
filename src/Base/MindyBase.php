<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/08/16
 * Time: 13:06
 */

declare(strict_types = 1);

namespace Mindy\Base;

use Exception;
use Mindy\ErrorHandler\ErrorHandler;

/**
 * Gets the application start timestamp.
 */
defined('MINDY_BEGIN_TIME') or define('MINDY_BEGIN_TIME', microtime(true));
/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 */
defined('MINDY_DEBUG') or define('MINDY_DEBUG', false);
/**
 * This constant defines how much call stack information (file name and line number) should be logged by Mindy::trace().
 * Defaults to 0, meaning no backtrace information. If it is greater than 0,
 * at most that number of call stacks will be logged. Note, only user application call stacks are considered.
 */
defined('MINDY_TRACE_LEVEL') or define('MINDY_TRACE_LEVEL', 0);
/**
 * This constant defines whether exception handling should be enabled. Defaults to true.
 */
defined('MINDY_ENABLE_EXCEPTION_HANDLER') or define('MINDY_ENABLE_EXCEPTION_HANDLER', true);
/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 */
defined('MINDY_ENABLE_ERROR_HANDLER') or define('MINDY_ENABLE_ERROR_HANDLER', true);
/**
 * Defines the Yii framework installation path.
 */
defined('MINDY_PATH') or define('MINDY_PATH', dirname(__FILE__));
/**
 * Defines the tests mode for application.
 */
defined('MINDY_TEST') or define('MINDY_TEST', false);

/**
 * Class MindyBase
 * @package Mindy\Base
 */
abstract class MindyBase
{
    /**
     * @var \Mindy\Base\Application
     */
    private static $_app;
    /**
     * @var null|ErrorHandler
     */
    private static $_errorHandler;

    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return \Mindy\Base\Application the application singleton, null if the singleton has not been created yet.
     */
    public static function app()
    {
        return self::$_app;
    }

    /**
     * Stores the application instance in the class static member.
     * This method helps implement a singleton pattern for CApplication.
     * Repeated invocation of this method or the CApplication constructor
     * will cause the throw of an exception.
     * To retrieve the application instance, use {@link app()}.
     * @param \Mindy\Base\BaseApplication $app the application instance. If this is null, the existing
     * application singleton will be removed.
     * @throws Exception if multiple application instances are registered.
     */
    public static function setApplication($app)
    {
        if (self::$_app === null || $app === null) {
            self::$_app = $app;
        } else {
            throw new Exception('Application can only be created once.');
        }
    }

    /**
     * Initializes the error handlers.
     * @void
     */
    protected static function registerErrorHandler()
    {
//        if (MINDY_ENABLE_EXCEPTION_HANDLER || MINDY_ENABLE_ERROR_HANDLER) {
//            self::$_errorHandler = new ErrorHandler();
//            if (MINDY_ENABLE_EXCEPTION_HANDLER) {
//                set_exception_handler([self::$_errorHandler, 'handleException']);
//            }
//            if (MINDY_ENABLE_ERROR_HANDLER) {
//                set_error_handler([self::$_errorHandler, 'handleError'], error_reporting());
//            }
//        }
    }

    public static function t($id, array $parameters = [], $domain = null, $locale = null) : string
    {
        return self::$_app->t($id, $parameters, $domain, $locale);
    }

    /**
     * Creates an application of the specified class.
     * @param string $class the application class name
     * @param mixed $config application configuration. This parameter will be passed as the parameter
     * to the constructor of the application class.
     * @return mixed the application instance
     */
    protected static function createApplication($class, $config = null)
    {
        self::registerErrorHandler();
        $app = new $class($config);
//        if (MINDY_ENABLE_EXCEPTION_HANDLER || MINDY_ENABLE_ERROR_HANDLER) {
//            self::$_errorHandler->setLogger($app->getLogger());
//        }
        return $app;
    }

    /**
     * Creates a Web application instance.
     * @param mixed $config application configuration.
     * If a string, it is treated as the path of the file that contains the configuration;
     * If an array, it is the actual configuration information.
     * Please make sure you specify the {@link CApplication::basePath basePath} property in the configuration,
     * which should point to the directory containing all application logic, template and data.
     * If not, the directory will be defaulted to 'protected'.
     * @param string $className
     * @return \Mindy\Base\Application
     */
    public static function getInstance($config = null, $className = '\Mindy\Base\Application')
    {
        return self::createApplication($className, $config);
    }
}