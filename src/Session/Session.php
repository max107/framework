<?php

namespace Mindy\Session;

use Countable;
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
     */
    public function setHandler(SessionAdapterInterface $handler)
    {
        $this->_handler = $handler;
    }

    /**
     * @return SessionAdapterInterface
     */
    public function getHandler() : SessionAdapterInterface
    {
        return $this->_handler;
    }

    /**
     * @return bool
     */
    public function isStarted() : bool
    {
        return $this->_handler->isStarted();
    }

    /**
     * @return bool
     */
    public function isClosed() : bool
    {
        return $this->_handler->isClosed();
    }

    /**
     * @return SessionAdapterInterface
     */
    public function start()
    {
        return $this->_handler->start();
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function set($name, $value)
    {
        return $this->_handler->set($name, $value);
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return $this->_handler->get($name, $defaultValue);
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
        return count($this->_handler);
    }

    /**
     * @return bool
     */
    public function clear() : bool
    {
        return $this->_handler->clear();
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->_handler->all();
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->_handler->getId();
    }
}