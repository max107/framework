<?php

namespace Mindy\Router;

use Mindy\Router\Exception\BadRouteException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class RouteCollector
 * @package Mindy\Router
 */
class RouteCollector
{
    const APPROX_CHUNK_SIZE = 10;
    /**
     * @var array
     */
    public $reverse = [];
    /**
     * @var RouteParser
     */
    private $routeParser;
    /**
     * @var array
     */
    private $staticRoutes = [];
    /**
     * @var array
     */
    private $regexToRoutesMap = [];
    /**
     * @var string|null prefix for group() method
     */
    private $_prefix;

    /**
     * @param RouteParser $routeParser
     */
    public function __construct(RouteParser $routeParser)
    {
        $this->routeParser = $routeParser;
    }

    /**
     * @param $name
     * @param array $args
     * @return string
     * @throws Exception\HttpRouteNotFoundException
     */
    public function reverse($name, $args = [])
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        $url = array();
        $replacements = is_null($args) ? [] : array_values($args);
        $variable = 0;
        if (!isset($this->reverse[$name])) {
            throw new BadRouteException("Route " . $name . " not found");
        }
        foreach ($this->reverse[$name] as $part) {
            if (!$part['variable']) {
                $url[] = $part['value'];
            } elseif (isset($replacements[$variable])) {
                if ($part['optional']) {
                    $url[] = '/';
                }
                $url[] = $replacements[$variable++];
            } elseif (!$part['optional']) {
                throw new BadRouteException("Expecting route variable '{$part['name']}'");
            }
        }
        return str_replace('//', '/', '/' . implode('', $url));
    }

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @return $this
     */
    public function addRoute($httpMethod, $route, $handler, array $params = [])
    {
        if (is_array($httpMethod) === false) {
            $httpMethod = [$httpMethod];
        }

        if (is_array($route)) {
            list($route, $name) = $route;
        }

        if ($this->_prefix) {
            $route = rtrim($this->_prefix, '/') . '/' . ltrim($route, '/');
        }

        // Don't use trim function, because route must be like "//".
        if (strpos($route, '/') === 0) {
            $route = substr($route, 1);
        }
        list($routeData, $reverseData) = $this->routeParser->parse($route);

        if (isset($name)) {
            $this->reverse[$name] = $reverseData;
        }

        foreach ($httpMethod as $method) {
            if (isset($routeData[1])) {
                $this->addVariableRoute($method, $routeData, $handler, $params);
            } else {
                $this->addStaticRoute($method, $routeData, $handler, $params);
            }
        }

        return $this;
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     * @param array $params
     */
    private function addStaticRoute($httpMethod, $routeData, $handler, array $params = [])
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$routeStr][$httpMethod])) {
            throw new BadRouteException("Cannot register two routes matching '$routeStr' for method '$httpMethod'");
        }

        foreach ($this->regexToRoutesMap as $regex => $routes) {
            if (isset($routes[$httpMethod]) && preg_match('~^' . $regex . '$~', $routeStr)) {
                throw new BadRouteException("Static route '$routeStr' is shadowed by previously defined variable route '$regex' for method '$httpMethod'");
            }
        }

        $this->staticRoutes[$routeStr][$httpMethod] = [$handler, [], $params];
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     * @param array $params
     */
    private function addVariableRoute($httpMethod, $routeData, $handler, array $params = [])
    {
        list($regex, $variables) = $routeData;

        if (isset($this->regexToRoutesMap[$regex][$httpMethod])) {
            throw new BadRouteException("Cannot register two routes matching '$regex' for method '$httpMethod'");
        }

        $this->regexToRoutesMap[$regex][$httpMethod] = [$handler, $variables, $params];
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function get($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::GET, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function head($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::HEAD, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function post($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::POST, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function put($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::PUT, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function delete($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::DELETE, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function options($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::OPTIONS, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $params
     * @return $this
     */
    public function any($route, $handler, $params = [])
    {
        return $this->addRoute(Dispatcher::ANY, $route, $handler, $params);
    }

    /**
     * @param $route
     * @param $classname
     * @return $this
     */
    public function restful($route, $classname)
    {
        $reflection = new ReflectionClass($classname);
        $validMethods = $this->getValidMethods();
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($validMethods as $valid) {
                if (stripos($method->name, $valid) === 0) {
                    $methodName = $this->camelCaseToDashed(substr($method->name, strlen($valid)));
                    $params = $this->buildControllerParameters($method);
                    $sep = $route === '/' || substr($route, strlen($route) - 1) === '/' ? '' : '/';
                    $this->addRoute($valid, $route . $sep . $methodName . $params, array($classname, $method->name));
                    break;
                }
            }
        }
        return $this;
    }

    /**
     * @param ReflectionMethod $method
     * @return string
     */
    private function buildControllerParameters(ReflectionMethod $method)
    {
        $params = '';

        foreach ($method->getParameters() as $param) {
            $params .= "/{" . $param->getName() . "}" . ($param->isOptional() ? '?' : '');
        }

        return $params;
    }

    /**
     * @param $string
     * @return string
     */
    private function camelCaseToDashed($string)
    {
        return strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst($string)));
    }

    /**
     * @return array
     */
    public function getValidMethods()
    {
        return [
            Dispatcher::ANY,
            Dispatcher::GET,
            Dispatcher::POST,
            Dispatcher::PUT,
            Dispatcher::DELETE,
            Dispatcher::HEAD,
            Dispatcher::OPTIONS
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (empty($this->regexToRoutesMap)) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    /**
     * @return array
     */
    private function generateVariableRouteData()
    {
        $chunkSize = $this->computeChunkSize(count($this->regexToRoutesMap));
        $chunks = array_chunk($this->regexToRoutesMap, $chunkSize, true);
        return array_map(array($this, 'processChunk'), $chunks);
    }

    /**
     * @param $count
     * @return float
     */
    private function computeChunkSize($count)
    {
        $numParts = max(1, round($count / self::APPROX_CHUNK_SIZE));
        return ceil($count / $numParts);
    }

    /**
     * @param $regexToRoutesMap
     * @return array
     */
    private function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $routes) {
            $firstRoute = reset($routes);
            $numVariables = count($firstRoute[1]);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);

            foreach ($routes as $httpMethod => $route) {
                $routeMap[$numGroups + 1][$httpMethod] = $route;
            }

            $numGroups++;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return [
            'regex' => $regex,
            'routeMap' => $routeMap
        ];
    }

    public function group($prefix, $callback)
    {
        $this->setPrefix($prefix);
        if (is_callable($callback)) {
            $callback($this);
        } else {
            foreach ($callback as $route) {
                $routePattern = rtrim($prefix, '/') . '/' . ltrim($route['route'], '/');
                $name = isset($route['name']) ? [$routePattern, $route['name']] : $routePattern;

                $this->addRoute(
                    $route['method'] ?? Dispatcher::ANY,
                    $name,
                    $route['handler'] ?? $route['callback'],
                    $route['params'] ?? []
                );
            }
        }
        $this->setPrefix(null);
        return $this;
    }

    /**
     * Set prefix for group() method
     * @param $prefix
     * @return $this
     */
    protected function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
        return $this;
    }

    /**
     * @param $prefix
     * @param callable $callback
     * @return $this
     */
    public function groupConfig($prefix, callable $callback)
    {
        $routes = $callback();
        foreach ($routes as $route) {
            $routePattern = rtrim($prefix, '/') . '/' . ltrim($route['route'], '/');
            $name = isset($route['name']) ? [$routePattern, $route['name']] : $routePattern;

            $this->addRoute(
                $route['method'] ?? Dispatcher::ANY,
                $name,
                $route['handler'] ?? $route['callback'],
                $route['params'] ?? []
            );
        }

        return $this;
    }
}
