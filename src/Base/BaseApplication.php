<?php

declare(strict_types = 1);

namespace Mindy\Base;

use Exception;
use Mindy\Base\Traits\ParamsTrait;
use Mindy\Base\Traits\StatePersisterTrait;
use Mindy\Di\ModuleManagerAwareTrait;
use Mindy\Di\ServiceLocatorAwareTrait;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class BaseApplication implements IApplication
{
    use StatePersisterTrait;
    use ParamsTrait;
    use ServiceLocatorAwareTrait;
    use ModuleManagerAwareTrait;

    /**
     * @var string the application name. Defaults to 'My Application'.
     */
    public $name = 'My Application';
    /**
     * @var null|callable
     */
    public $loggerConfigure = null;
    /**
     * @var string
     */
    private $_id;
    /**
     * @var string
     */
    private $_basePath;
    /**
     * @var string
     */
    private $_runtimePath;
    /**
     * @var string
     */
    private $_modulePath;
    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @param $config
     * @return array
     * @throws Exception
     */
    protected function fetchConfig($config) : array
    {
        // set basePath at early as possible to avoid trouble
        if (is_string($config)) {
            $data = require($config);
            if (!is_array($data)) {
                throw new Exception('Incorrent configuration type');
            }

            return $data;
        } else if ($config === null) {
            return [];
        } else if (is_array($config)) {
            return $config;
        }

        throw new Exception('Unsupported configuration type');
    }

    /**
     * Returns the directory that contains the application modules.
     * @return string the directory that contains the application modules. Defaults to the 'modules' subdirectory of {@link basePath}.
     */
    public function getModulePath() : string
    {
        if ($this->_modulePath !== null) {
            return $this->_modulePath;
        } else {
            return $this->_modulePath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Modules';
        }
    }

    /**
     * Sets the directory that contains the application modules.
     * @param string $value the directory that contains the application modules.
     * @throws Exception if the directory is invalid
     */
    public function setModulePath($value)
    {
        if (($this->_modulePath = realpath($value)) === false || !is_dir($this->_modulePath)) {
            throw new Exception('The module path "' . $value . '" is not a valid directory.');
        }
    }

    /**
     * Terminates the application.
     * This method replaces PHP's exit() function by calling
     * {@link onEndRequest} before exiting.
     * @param integer $status exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param boolean $exit whether to exit the current request. This parameter has been available since version 1.1.5.
     * It defaults to true, meaning the PHP's exit() function will be called at the end of this method.
     */
    public function end($status = 0, $exit = true)
    {
        if ($exit) {
            exit($status);
        }
    }

    /**
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     * @throws Exception if the directory does not exist or is not writable
     */
    public function setRuntimePath($path)
    {
        if (($runtimePath = realpath($path)) === false || !is_dir($runtimePath) || !is_writable($runtimePath)) {
            throw new Exception('Application runtime path "' . $path . '" is not valid. Please make sure it is a directory writable by the Web server process.');
        }
        $this->_runtimePath = $runtimePath;
    }

    /**
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files. Defaults to 'protected/runtime'.
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath !== null) {
            return $this->_runtimePath;
        } else {
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
            return $this->_runtimePath;
        }
    }

    /**
     * Sets the root directory of the application.
     * This method can only be invoked at the begin of the constructor.
     * @param string $path the root directory of the application.
     * @throws Exception if the directory does not exist.
     */
    public function setBasePath($path)
    {
        if (($this->_basePath = realpath($path)) === false || !is_dir($this->_basePath)) {
            throw new Exception('Application base path "' . $path . '" is not a valid directory.');
        }
    }

    /**
     * Returns the root path of the application.
     * @return string the root directory of the application. Defaults to 'protected'.
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * Sets the unique identifier for the application.
     * @param string $id the unique identifier for the application.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Returns the unique identifier for the application.
     * @return string the unique identifier for the application.
     */
    public function getId()
    {
        if ($this->_id === null) {
            $this->_id = sprintf('%x', crc32($this->getBasePath() . $this->name . self::getVersion()));
        }

        return $this->_id;
    }

    /**
     * @return string the version of Mindy
     */
    public static function getVersion() : string
    {
        return '3.0beta';
    }

    /**
     * Runs the application.
     * This method loads static application components. Derived classes usually overrides this
     * method to do more application-specific tasks.
     * Remember to call the parent implementation so that static application components are loaded.
     */
    public function run()
    {
        register_shutdown_function([$this, 'end'], 0, false);

        $this->runInternal();
    }

    /**
     * @return mixed
     */
    abstract protected function runInternal();

    /**
     * @param LoggerInterface $logger
     * @return $this|BaseApplication
     */
    public function setLogger(LoggerInterface $logger) : self
    {
        $this->_logger = $logger;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger() : LoggerInterface
    {
        if ($this->_logger === null) {
            // create a log channel
            $logger = new Logger('default');

            if (is_callable($this->loggerConfigure)) {
                $logger = $this->loggerConfigure->__invoke($logger);
            } else {
                $level = MINDY_DEBUG ? Logger::DEBUG : Logger::ERROR;
                $handler = new RotatingFileHandler($this->getRuntimePath() . DIRECTORY_SEPARATOR . 'application.log', 5, $level);
                $logger->pushHandler($handler);
            }

            $this->_logger = $logger;
        }

        return $this->_logger;
    }
}