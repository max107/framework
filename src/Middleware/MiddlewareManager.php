<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 19:49
 */

namespace Mindy\Middleware;

use Mindy\Helper\Creator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class MiddlewareManager
 * @package Mindy\Middleware
 */
class MiddlewareManager
{
    /**
     * @var array|\Closure[]|callable[]
     */
    private $_queue = [];

    /**
     * MiddlewareManager constructor.
     * @param array $queue
     */
    public function __construct(array $queue = [])
    {
        $this->_queue = $queue;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $entry = array_shift($this->_queue);
        $middleware = $this->resolve($entry);
        return $middleware($request, $response, $this);
    }

    /**
     * Converts a queue entry to a callable, using the resolver if present.
     * @param mixed|callable $entry The queue entry.
     * @return callable
     */
    protected function resolve($entry)
    {
        if (!$entry) {
            // the default callable when the queue is empty
            return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
                return $response;
            };
        } else if (is_array($entry)) {
            $entry = Creator::createObject($entry);
        }
        return $entry;
    }
}