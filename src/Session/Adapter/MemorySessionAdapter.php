<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:53
 */

declare(strict_types = 1);

namespace Mindy\Session\Adapter;

class MemorySessionAdapter implements SessionAdapterInterface
{
    /**
     * @var bool
     */
    protected $started = false;
    /**
     * @var bool
     */
    protected $closed = false;
    /**
     * @var array
     */
    private $_data = [];
    /**
     * @var string
     */
    private $_id;
    /**
     * @var string
     */
    private $_name;

    /**
     * @return bool
     */
    public function isStarted() : bool
    {
        return $this->started;
    }

    /**
     * @return bool
     */
    public function isClosed() : bool
    {
        return $this->closed;
    }

    /**
     * @return SessionAdapterInterface
     */
    public function start() : SessionAdapterInterface
    {
        if ($this->isStarted()) {
            return $this;
        }

        $this->started = true;
        $this->closed = false;

        return $this;
    }

    /**
     * @return mixed
     */
    public function set($name, $value) : bool
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        $this->_data[$name] = $value;
        return true;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : $defaultValue;
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
    public function count() : int
    {
        return count($this->_data);
    }

    /**
     * @return bool
     */
    public function clear() : bool
    {
        $this->_data = [];
        return true;
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return hash('sha256', uniqid('ss_mock_', true));
    }

    /**
     * @param string $id
     * @return bool
     */
    public function setId(string $id) : bool
    {
        $this->_id = $id;
        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function setName(string $name) : bool
    {
        $this->_name = $name;
        return true;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->_name;
    }

    /**
     * @param bool $removeOld
     * @return mixed
     */
    public function regenerateID(bool $removeOld = false) : bool
    {
        return true;
    }
}