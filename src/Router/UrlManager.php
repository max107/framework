<?php

namespace Mindy\Router;

/**
 * Class UrlManager
 * @package Mindy\Router
 */
class UrlManager extends Dispatcher
{
    /**
     * @var string
     */
    public $urlsAlias = 'App.config.urls';
    /**
     * @var null
     */
    public $patterns = [];

    /**
     * UrlManager constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }

        parent::__construct($this->fetchRoutes());
        $this->init();
    }

    protected function parseRoutes(RouteCollector $collector, array $patterns)
    {
        foreach ($patterns as $prefix => $params) {
            if (is_string($prefix) && is_array($params)) {
                if (isset($params['routes'])) {
                    $collector->group($prefix, $params['routes'], $params['namespace']);
                }
            } else if (is_string($prefix) && is_callable($params)) {
                $collector->addRoute(Dispatcher::ANY, $prefix, $params);
            } else {
                $collector->group('', $params);
            }
        }
    }

    /**
     * @return RouteCollector
     */
    protected function fetchRoutes()
    {
        $collector = new RouteCollector(new RouteParser);
        $this->parseRoutes($collector, $this->patterns);
        return $collector;
        /*
        if (class_exists('\Mindy\Base\Mindy') && \Mindy\Base\Mindy::app() && \Mindy\Base\Mindy::app()->hasComponent('cache')) {
            $cacheKey = 'routes';

            $cache = \Mindy\Base\Mindy::app()->cache;
            $data = $cache->get($cacheKey);
            if ($data === false) {
                $patterns = new Patterns(empty($this->patterns) ? $this->urlsAlias : $this->patterns);
                $data = $patterns->getRouteCollector();
                $cache->set($cacheKey, $data, 3600);
            }
            return $data;
        } else {
            $patterns = new Patterns(empty($this->patterns) ? $this->urlsAlias : $this->patterns);
            return $patterns->getRouteCollector();
        }
        */
    }

    public function init()
    {
    }

    /**
     * @param $prefix
     * @param Patterns $patterns
     * @throws \Exception
     */
    public function addPattern($prefix, Patterns $patterns)
    {
        $patterns->parse($this->collector, $patterns->getPatterns(), $prefix);
    }

    /**
     * @param $name
     * @param array $args
     * @return string
     */
    public function reverse($name, $args = [])
    {
        if (is_array($name)) {
            $args = $name;
            $name = $name[0];
            unset($args[0]);
        }
        return parent::reverse($name, $args);
    }
}
