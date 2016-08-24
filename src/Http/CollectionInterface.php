<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 19:23
 */

namespace Mindy\Http;

interface CollectionInterface
{
    /**
     * @return array
     */
    public function all();

    /**
     * @return int
     */
    public function count();

    /**
     * @param $key
     * @param null $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null);

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value);

    /**
     * @param array $data
     * @return mixed
     */
    public function merge(array $data);

    /**
     * @void
     */
    public function clear();

    /**
     * @param $key
     * @return mixed
     */
    public function remove($key);
}