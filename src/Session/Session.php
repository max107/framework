<?php

namespace Mindy\Session;

use Countable;
use Exception;
use Mindy\Creator\Creator;
use Mindy\Session\Adapter\SessionAdapterInterface;

/**
 * Class Session
 * @package Mindy\Session
 */
class Session implements Countable
{
    /**
     * @var bool
     */
    public $autoStart = false;
    /**
     * @var SessionAdapterInterface
     */
    private $_handler;

    /**
     * Session constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);

        if (isset($config['handler']) === false) {
            register_shutdown_function([$this, 'close']);
        }

        if ($this->autoStart) {
            $this->start();
        }
    }

    /**
     * @param array $config
     */
    protected function configure(array $config)
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param SessionAdapterInterface $handler
     * @throws Exception
     */
    public function setHandler($handler)
    {
        if (($handler instanceof SessionAdapterInterface) === false) {
            $handler = Creator::createObject($handler);
        }

        $this->_handler = $handler;

        if (session_set_save_handler($handler, true) === false) {
            throw new Exception("Failed to set custom session handlers");
        }
    }

    /**
     * @return SessionAdapterInterface
     */
    public function getHandler() : SessionAdapterInterface
    {
        return $this->_handler;
    }

    /**
     * @return SessionAdapterInterface
     */
    public function start()
    {
        return $this->getHandler()->start();
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function set($name, $value)
    {
        return $this->getHandler()->set($name, $value);
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return $this->getHandler()->get($name, $defaultValue);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->getHandler());
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->getHandler()->all();
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->getHandler()->getId();
    }

    /**
     * Ends the current session and store session data.
     */
    public function close()
    {
        if ($this->isStarted()) {
            session_write_close();
        }
    }

    /**
     * @return boolean whether the session has started
     */
    public function isStarted() : bool
    {
        return $this->getHandler()->isStarted();
    }

    /**
     * @return bool
     */
    public function clear() : bool
    {
        return $this->getHandler()->clear();
    }
}