<?php

namespace Mindy\Helper;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Serializable;
use Traversable;

/**
 * Class Collection
 * @package Mindy\Helper
 */
class Collection implements Countable, Serializable, IteratorAggregate
{
    /**
     * @var array
     */
    private $_data = [];

    public function __construct(array $data)
    {
        $this->_data = $data;
    }

    public function add($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->_data);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->_data[$key] : $default;
    }

    public function all()
    {
        return $this->_data;
    }

    public function clear()
    {
        $this->_data = [];
        return $this;
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->_data[$key]);
        }
    }

    public function toJson()
    {
        return Json::encode($this->_data);
    }

    public function merge(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        return $this;
    }

    /**
     * Merges two or more arrays into one recursively.
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     * @param array $a array to be merged to
     * @param array $b array to be merged from. You can specify additional
     * arrays via third argument, fourth argument etc.
     * @return array the merged array (the original arrays are not changed.)
     * @see mergeWith
     */
    public static function mergeArray($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_integer($k)) {
                    isset($res[$k]) ? $res[] = $v : $res[$k] = $v;
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::mergeArray($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer. The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->_data);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized
     * The string representation of the object.
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->_data = unserialize($serialized);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }
}