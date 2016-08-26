<?php

namespace Mindy\Router;

use Exception;
use Mindy\Helper\Alias;

/**
 * Class Patterns
 * @package Mindy\Router
 */
class Patterns
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
     * @var string
     */
    private $namespaceDelimeter = ':';

    /**
     * @param $patterns
     * @param string $namespace
     * @throws Exception
     */
    public function __construct($patterns, $namespace = '')
    {
        if (is_string($patterns)) {
            $tmp = Alias::get($patterns);
            if (!$tmp) {
                $tmp = $patterns;
            } else {
                $tmp .= '.php';
            }

            if (is_file($tmp)) {
                $patterns = require $tmp;
            } else {
                $patterns = [];
            }

            if (!is_array($patterns)) {
                throw new Exception("Patterns must be a an array or alias to routes file: $patterns");
            }
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
        if (is_callable($callback)) {
            return $callback;
        } else if (is_string($callback) && strpos($callback, ':') !== false) {
            return explode(':', $callback);
        } else if (is_array($callback)) {
            if (count($callback) == 2) {
                return $callback;
            } else {
                return [key($callback), array_shift($callback)];
            }
        } else {
            return null;
        }
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
            if ($params instanceof Patterns || $params instanceof CustomPatterns) {
                $params->parse($collector, $params->getPatterns(), trim($parentPrefix, '/') . $urlPrefix);
            } else {
                $method = Dispatcher::ANY;
                if (is_callable($params)) {
                    $collector->addRoute($method, trim($parentPrefix, '/') . $urlPrefix, $params);
                } else if (is_array($params)) {
                    if (array_key_exists('callback', $params) === false && array_key_exists('restful', $params) === false) {
                        throw new Exception('Missing callback or controller key in: ' . print_r($params, true));
                    }

                    if (isset($params['callback'])) {
                        $callback = $this->fetchCallback($params['callback']);
                        if ($callback === null) {
                            throw new Exception("Incorrect callback in rule " . $params['name']);
                        }

                        if (isset($params['method']) && in_array(strtoupper($params['method']), $collector->getValidMethods())) {
                            $method = strtoupper($params['method']);
                        }

                        if (isset($params['name'])) {
                            $name = $params['name'];
                            if (!empty($this->namespace)) {
                                $name = $this->namespace . $this->namespaceDelimeter . $params['name'];
                            }

                            $route = [trim($parentPrefix, '/') . $urlPrefix, $name];
                        } else {
                            $route = trim($parentPrefix, '/') . $urlPrefix;
                        }

                        $collector->addRoute($method, $route, $callback, isset($params['params']) ? $params['params'] : []);
                    } else if (isset($params['restful'])) {
                        $collector->restful(trim($parentPrefix, '/') . $urlPrefix, $params['restful']);
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
