<?php

namespace Mindy\Router;

use Exception;
use Mindy\Helper\Alias;

/**
 * Class Patterns
 * @package Mindy\Router
 */
class Patterns extends BasePatterns
{
    /**
     * @var array
     */
    public $patterns = [];
    /**
     * @var string
     */
    public $namespace = '';
    /**
     * @var string
     */
    protected $parentPrefix;

    /**
     * @param $patterns
     * @param string $namespace
     * @throws Exception
     */
    public function __construct($patterns, $namespace = '')
    {
        if (is_string($patterns) && is_file($patterns)) {
            $patterns = include($patterns);
        }

        if (is_array($patterns) === false) {
            throw new Exception("Patterns must be a an array or alias to routes file: " . '?');
        }

        $this->patterns = $patterns;
        $this->namespace = $namespace;
    }

    /**
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    protected function fetchCallback($callback)
    {
        if (is_string($callback) && strpos($callback, ':') !== false) {
            $callback = explode(':', $callback);
        }

        if (is_callable($callback)) {
            return $callback;
        }

        if (is_array($callback) && count($callback) == 1) {
            $callback = [key($callback), array_shift($callback)];
        }

        return $callback;
    }

    /**
     * @param RouteCollector $collector
     * @param array $patterns
     * @param string $parentPrefix
     * @throws Exception
     */
    public function parse(RouteCollector $collector, array $patterns, $parentPrefix = '')
    {
        foreach ($patterns as $urlPrefix => $params) {
            if ($params instanceof PatternsInterface) {
                $params->parse($collector, $params->getPatterns(), trim($parentPrefix, '/') . $urlPrefix);
            } else {
                $route = trim($parentPrefix, '/') . $params['route'];

                $method = isset($params['method']) ? strtoupper($params['method']) : Dispatcher::ANY;
                if (in_array($method, $collector->getValidMethods()) === false) {
                    throw new Exception('Unknown route method');
                }

                if ($params instanceof \Closure) {
                    $collector->addRoute($method, trim($parentPrefix, '/') . $urlPrefix, $params);
                } else if (is_array($params)) {
                    
                    if (array_key_exists('callback', $params) || array_key_exists('handler', $params)) {
                        $handler = isset($params['handler']) ? $params['handler'] : $params['callback'];
                        $callback = $this->fetchCallback($handler);
                        if ($callback === null) {
                            throw new Exception("Incorrect callback in rule" . print_r($params, true));
                        }

                        if (isset($params['name'])) {
                            $name = $params['name'];
                            if (!empty($this->namespace)) {
                                $name = $this->namespace . $this->namespaceDelimeter . $params['name'];
                            }

                            $route = [$route, $name];
                        }

                        $collector->addRoute($method, $route, $callback, $params['params'] ?? $params['params']);
                    } else if (array_key_exists('restful', $params) === false) {
                        $collector->restful($urlPrefix, $params['restful']);
                    } else {
                        throw new Exception('Missing "handler" or "restful" key in: ' . print_r($params, true));
                    }
                }
            }
        }
    }

    /**
     * @return RouteCollector
     */
    public function getRouteCollector()
    {
        $collector = new RouteCollector(new RouteParser);
        $this->parse($collector, $this->getPatterns());
        return $collector;
    }
}
