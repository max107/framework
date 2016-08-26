<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 19:43
 */

namespace Mindy\Router;

use Exception;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class HandlerResolver
{
    public function resolve($data)
    {
        list($handler, $vars, $params) = $data;

        if ($handler instanceof \Closure) {
            $method = new ReflectionFunction($handler);
            return $method->invokeArgs($this->parseParams($method, $vars));
        } else if (is_array($handler)) {
            list($handler, $actionName) = $handler;
            $handlerInstance = new $handler;

            $method = new ReflectionMethod($handlerInstance, $actionName);
            return $method->invokeArgs($handlerInstance, $this->parseParams($method, $vars));
        } else {
            throw new Exception('Unknown handler type');
        }
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