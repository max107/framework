<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 19:46
 */

namespace Mindy\Router;

interface IHandlerResolver
{
    public function resolve($data);
}