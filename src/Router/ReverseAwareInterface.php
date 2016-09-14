<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 13:42
 */

namespace Mindy\Router;

interface ReverseAwareInterface
{
    /**
     * @param $route
     * @param null $data
     * @return string
     */
    public function reverse($route, $data = null) : string;
}