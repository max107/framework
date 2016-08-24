<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:56
 */

namespace Mindy\Http;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest as ServerRequestGuzzle;

class Request extends ServerRequestGuzzle
{
    use Legacy;

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function fromGlobals()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = self::getUriFromGlobals();
        $body = new LazyOpenStream('php://input', 'r+');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $serverRequest = new self($method, $uri, getallheaders(), $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    /**
     * @return bool
     */
    public function isXhr()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
}
