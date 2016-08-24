<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 20:10
 */

namespace Mindy\Http;

/**
 * Class CollectionAwareTrait
 * @package Mindy\Http
 * @property CollectionInterface collection
 */
trait CollectionAwareTrait
{
    /**
     * @param $key
     * @return mixed
     */
    public function remove($key)
    {
        return $this->collection->remove($key);
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->collection->clear();
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->collection->set($key, $value);
        return $this;
    }

    /**
     * @param $key
     * @param null $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        return $this->collection->get($key, $defaultValue);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function merge(array $data)
    {
        return $this->collection->merge($data);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->collection->count();
    }

    public function all()
    {
        return $this->collection->all();
    }
}