<?php

namespace Mindy\Router;

use Exception;
use Mindy\Router\Exception\HttpMethodNotAllowedException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Class Dispatcher
 * @package Mindy\Router
 */
class Dispatcher
{
    const ANY = 'ANY';
    const GET = 'GET';
    const HEAD = 'HEAD';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';

    /**
     * @var RouteCollector
     */
    public $collector;
    /**
     * @var
     */
    public $matchedRoute;
    /**
     * @var
     */
    private $staticRouteMap;
    /**
     * @var
     */
    private $variableRouteData;
    /**
     * @var callable
     */
    protected $handlerResolver;

    /**
     * Dispatcher constructor.
     * @param RouteCollector $collector
     * @param callable $handlerResolver
     */
    public function __construct(RouteCollector $collector, callable $handlerResolver = null)
    {
        $this->handlerResolver = $handlerResolver ?? new HandlerResolver();
        $this->collector = $collector;
        list($this->staticRouteMap, $this->variableRouteData) = $collector->getData();
    }

    /**
     * @param $name
     * @param array $args
     * @return string
     */
    public function reverse($name, $args = [])
    {
        return $this->collector->reverse($name, $args);
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @return mixed
     */
    public function dispatch($httpMethod, $uri)
    {
        $data = $this->dispatchRoute(strtoupper($httpMethod), ltrim(strtok($uri, '?'), '/'));
        if ($data === false) {
            return false;
        }

        return $this->getResponse($data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getResponse(array $data)
    {
        $resolver = $this->handlerResolver;
        return $resolver($data);
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @return mixed
     */
    public function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws HttpMethodNotAllowedException
     */
    private function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod])) {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }

        return $routes[$httpMethod];
    }

    /**
     * @param $routes
     * @param $httpMethod
     * @return mixed
     * @throws HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod)
    {
        $additional = [self::ANY];

        $httpMethod = strtoupper($httpMethod);
        if ($httpMethod === self::HEAD) {
            $additional[] = self::GET;
        }

        foreach ($additional as $method) {
            if (isset($routes[$method])) {
                return $method;
            }
        }

        $this->matchedRoute = $routes;

        throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @return bool
     * @throws HttpMethodNotAllowedException
     */
    private function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            $count = count($matches);

            while (!isset($data['routeMap'][$count++])) ;

            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod])) {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            }

            foreach (array_values($routes[$httpMethod][1]) as $i => $varName) {
                // if (!isset($matches[$i + 1]) || $matches[$i + 1] === '') {
                if (!isset($matches[$i + 1])) {
                    unset($routes[$httpMethod][1][$varName]);
                } else {
                    $routes[$httpMethod][1][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

        // throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
        return false;
    }

}
