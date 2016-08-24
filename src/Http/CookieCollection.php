<?php

namespace Mindy\Http;

/**
 * Class CookieCollection
 * @package Mindy\Http
 */
class CookieCollection extends Collection
{
    /**
     * @param $key
     * @param $value
     * @return $this|void
     */
    public function set($key, $value)
    {
        $cookie = $value instanceof Cookie ? $value : new Cookie($key, $value);
        parent::set($key, $cookie);
        setcookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        return $this;
    }

    public function get($key, $defaultValue = null)
    {
        $value = parent::get($key, $defaultValue);
        if (($value instanceof Cookie) === false) {
            return new Cookie($key, $value);
        }

        return $value;
    }

    public function remove($key)
    {
        $name = $key;
        if ($key instanceof Cookie) {
            $name = $key->name;
        }

        /** @var Cookie $cookie */
        if ($this->has($name)) {
            $cookie = $this->get($name);
            setcookie($cookie->name, '', 0, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
        parent::remove($name);
    }
}