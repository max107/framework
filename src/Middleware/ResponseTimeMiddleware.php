<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 17:21
 */

namespace Mindy\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseTimeMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $server = $request->getServerParams();
        if (!isset($server['REQUEST_TIME_FLOAT'])) {
            $server['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        /** @var ResponseInterface $response */
        $response = $next($request, $response);
        $time = (microtime(true) - $server['REQUEST_TIME_FLOAT']) * 1000;
        return $response->withHeader('X-Response-Time', sprintf('%2.3fms', $time));
    }
}