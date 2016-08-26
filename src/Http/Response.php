<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:40
 */

namespace Mindy\Http;

use GuzzleHttp\Psr7\Response as ResponseGuzzle;

class Response extends ResponseGuzzle
{
    /**
     * @var array
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
     * @param $cookie
     * @return Response
     */
    public function withCookie($cookie)
    {
        $new = clone $this;
        if (is_array($cookie)) {
            $name = $cookie['name'];
            unset($cookie['name']);
            $value = $cookie['value'];
            unset($cookie['value']);
            $cookie = new Cookie($name, $value, $cookie);
        }
        $new->cookies[] = $cookie;
        return $new;
    }
}