<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 17:44
 */

namespace Mindy\Session;

use Mindy\Http\CollectionInterface;

class SessionCollection implements CollectionInterface
{
    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @param array $data
     * @return $this
     */
    public function merge(array $data)
    {
        foreach($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
        return $this;
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @param $key
     * @param null $defaultValue
     * @return null
     */
    public function get($key, $defaultValue = null)
    {
        $all = $this->all();
        return array_key_exists($key, $all) ? $all[$key] : $defaultValue;
    }

    /**
     * @return array
     */
    public function all()
    {
        return isset($_SESSION) && $_SESSION ? $_SESSION : [];
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
        return $this;
    }

    public function count()
    {
        return count($_SESSION);
    }

    public function clear()
    {
        $_SESSION = [];
        return $this;
    }
}