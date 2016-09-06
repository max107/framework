<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:40
 */

namespace Mindy\Http\Response;

use GuzzleHttp\Psr7\Response as ResponseGuzzle;
use Mindy\Http\CacheTrait;
use Mindy\Http\Cookie;

class Response extends ResponseGuzzle
{
    use CacheTrait;

    /**
     * @var array|Cookie[]
     */
    private $cookies = [];

    /**
     * @return Cookie[]
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param string $key
     * @return Cookie|null
     */
    public function getCookie(string $key)
    {
        return array_key_exists($key, $this->cookies) ? $this->cookies[$key] : null;
    }

    /**
     * @param $cookie
     * @return Response
     */
    public function withCookie($cookie)
    {
        $new = clone $this;
        if (is_array($cookie)) {
            $name = $cookie['name'];
            $value = $cookie['value'];
            unset($cookie['name'], $cookie['value']);
            $cookie = new Cookie($name, $value, $cookie);
        }
        $new->cookies[$cookie->getName()] = $cookie;
        return $new;
    }

    public function withoutCookie(string $name)
    {
        if (isset($cookies[$name])) {
            $new = clone $this;
            $cookies = $this->getCookies();
            $oldCookie = $cookies[$name];
            unset($new->cookies[$name]);
            $new->cookies[$name] = $oldCookie->setExpires(0);
            return $new;
        }

        return $this;
    }
}