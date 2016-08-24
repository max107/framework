<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:41
 */

namespace Mindy\Http;

use ArrayAccess;
use IteratorAggregate;

/**
 * Class Collection
 * @package Mindy\Http
 */
class Collection implements CollectionInterface, IteratorAggregate, ArrayAccess
{
    /**
     * @var array|null
     */
    private $_data = [];

    /**
     * Collection constructor.
     * @param null $data
     */
    public function __construct(array $data)
    {
        if (empty($data)) {
            $data = [];
        }

        $this->_data = $data;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->_data;
    }

    /**
     * @param $key
     * @param null $defaultValue
     * @return array|null
     */
    public function get($key, $defaultValue = null)
    {
        if ($this->has($key)) {
            return $this->_data[$key];
        }

        return $defaultValue;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->_data);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function merge(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        unset($this->_data[$key]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * @void
     */
    public function clear()
    {
        $this->_data = [];
        return $this;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}