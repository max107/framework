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
use Mindy\Creator\Creator;
use Mindy\Middleware\MiddlewareManager;
use Mindy\Router\Exception\HttpMethodNotAllowedException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class HandlerResolver
{
    protected function checkPermissions(array $params)
    {
        $user = app()->getUser();
        if (isset($params['rules'])) {
            foreach ($params['rules'] as $ruleConfig) {
                $rule = Creator::createObject($ruleConfig);
                if ($rule->can($user) === false) {
                    throw new HttpMethodNotAllowedException();
                }
            }
        }
    }

    protected function updateRequest(array $vars)
    {
        $request = app()->http->getRequest();
        app()->http->setRequest($request->withQueryParams(array_merge($request->getQueryParams(), $vars)));
    }

    protected function runMiddleware($params)
    {
        if (isset($params['middleware'])) {
            $middleware = new MiddlewareManager($params['middleware']);
            $request = app()->http->getRequest();
            $response = app()->http->getResponse();

            $newResponse = $middleware($request, $response);
            app()->http->setResponse($newResponse);
        }
    }

    public function __invoke($data)
    {
        list($handler, $vars, $params) = $data;

        if (app()) {
            $this->checkPermissions($params);
            $this->updateRequest($vars);
            $this->runMiddleware($params);
        }

        if ($handler instanceof \Closure) {
            $method = new ReflectionFunction($handler);
            return $method->invokeArgs($this->parseParams($method, $vars));
        }

        if (is_string($handler)) {
            $handler = explode(':', $handler);
        }

        if (is_array($handler)) {
            list($handler, $actionName) = $handler;

            $reflect = new ReflectionClass($handler);
            $instance = $reflect->newInstance();

            return call_user_func_array([$instance, 'run'], [
                $actionName,
                $this->parseParams(new ReflectionMethod($instance, $actionName), $vars)
            ]);
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

            if (isset($value)) {
                $ps[] = $value;
            }
        }

        return $ps;
    }
}