<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 19:43
 */

namespace Mindy\Router;

use Exception;
use function Mindy\app;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class HandlerResolver
{
    public function resolve($data)
    {
        list($handler, $vars, $params) = $data;

        $request = app()->http->getRequest();
        app()->http->setRequest($request->withQueryParams(array_merge($request->getQueryParams(), $vars)));

        if ($handler instanceof \Closure) {
            $method = new ReflectionFunction($handler);
            return $method->invokeArgs($this->parseParams($method, $vars));
        }

        if (is_string($handler)) {
            $handler = explode(':', $handler);
        }

        if (is_array($handler)) {
            list($handler, $actionName) = $handler;
            $handlerInstance = new $handler;

            $method = new ReflectionMethod($handlerInstance, $actionName);
            return $method->invokeArgs($handlerInstance, $this->parseParams($method, $vars));
        }

        throw new Exception('Unknown handler type');
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @param $params
     * @return array
     */
    protected function parseParams(ReflectionFunctionAbstract $method, $params) : array
    {
        $ps = [];
        foreach ($method->getParameters() as $i => $param) {
            if ($param->isDefaultValueAvailable()) {
                $value = $param->getDefaultValue();
            }
            if (isset($params[$param->getName()]) && $params[$param->getName()] !== '') {
                $value = $params[$param->getName()];
            }
            $ps[] = $value;
        }

        return $ps;
    }
}