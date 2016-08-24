<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 23:04
 */

namespace Mindy\Http;

use Exception;
use function GuzzleHttp\Psr7\stream_for;
use Mindy\Base\Mindy;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Configurator;
use Mindy\Session\HttpSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Http
 * @package Mindy\Http
 * @property CookieCollection cookies
 */
class Http
{
    use Configurator;
    use LegacyHttp;

    /**
     * @var bool
     */
    public $enableCsrfValidation = true;
    /**
     * @var array
     */
    public $settings = [];
    /**
     * @var CookieCollection
     */
    public $cookies;
    /**
     * @var Collection
     */
    public $get;
    /**
     * @var Collection
     */
    public $post;
    /**
     * @var HttpSession
     */
    public $session;
    /**
     * @var array
     */
    protected $defaultSettings = [
        'responseChunkSize' => 4096,
    ];
    /**
     * @var callable
     */
    public $middleware;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Response
     */
    protected $response;
    /**
     * @var bool
     */
    private $_sended = false;

    /**
     * Http constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $session = null;
        foreach ($config as $key => $value) {
            if ($key === 'session') {
                if (is_array($value) || is_string($value)) {
                    $session = Creator::createObject($value);
                } else {
                    $session = $value;
                }
                continue;
            }
            $this->{$key} = $value;
        }

        $this->request = Request::fromGlobals();

        $this->cookies = new CookieCollection($this->getRequest()->getCookieParams());
        $this->get = new Collection($this->getRequest()->getQueryParams());
        $this->post = new Collection($this->getRequest()->getServerParams());
        $this->files = new Collection($this->getRequest()->getUploadedFiles());

        $this->session = $session ? $session : new HttpSession([
            'autoStart' => false,
            'iniOptions' => [
                'gc_maxlifetime' => 60 * 60 * 24
            ]
        ]);
        $this->flash = new FlashCollection($this->session);

        $this->csrf = new Csrf($this);

        $response = new Response();
        $this->response = $this->withMiddleware($this->request, $response);
        if (in_array($this->response->getStatusCode(), [301, 302])) {
            $this->send($this->response);
        }
    }

    /**
     * @return bool
     */
    public function getIsAjax()
    {
        return $this->getRequest()->isXhr();
    }

    public function __get($name)
    {
        if ($name === 'http') {
            return $this;
        } else if ($name === 'requestUri' || $name === 'path') {
            return $this->getRequest()->getRequestTarget();
        } else if ($name === 'isAjax') {
            return $this->getRequest()->isXhr();
        } else if ($name === 'isPost') {
            return strtoupper($this->getRequest()->getMethod()) === 'POST';
        }

        return $this->{$name};
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Request|\Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * @see https://tools.ietf.org/html/rfc7231
     *
     * @param ResponseInterface $response
     * @return bool
     */
    public function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }
        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Apply middlewares to response
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function withMiddleware(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->middleware === null) {
            return $response;
        }

        $middleware = $this->middleware;
        return $middleware($request, $response);
    }

    /**
     * Send the response the client
     * @param ResponseInterface $response
     * @throws Exception
     */
    public function send(ResponseInterface $response)
    {
        if ($this->_sended) {
            throw new Exception('Response already sended');
        }
        $this->_sended = true;
        $this->setResponse($this->withMiddleware($this->getRequest(), $response));

        $response = $this->getResponse();

        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $chunkSize = $this->getSettings()['responseChunkSize'];
            $contentLength = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }
            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
        Mindy::app()->end();
    }

    /**
     * Return request global settings
     * @return array
     */
    public function getSettings()
    {
        return array_merge($this->defaultSettings, $this->settings);
    }

    /**
     * Refreshes the current page.
     * The effect of this method call is the same as user pressing the
     * refresh button on the browser (without post data).
     * @param string $anchor the anchor that should be appended to the redirection URL.
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     */
    public function refresh($anchor = '')
    {
        $this->redirect($this->getRequest()->getRequestTarget() . $anchor);
    }

    /**
     * @throws Exception
     */
    public function redirect($url, $data = null, $status = 302)
    {
        if (is_object($url) && method_exists($url, 'getAbsoluteUrl')) {
            $url = $url->getAbsoluteUrl();
        } else if (is_string($url) && strpos($url, ':') !== false) {
            $url = $this->resolveRoute($url, $data);
        }

        $response = $this->getResponse()
            ->withStatus($status)
            ->withHeader('Location', $url);
        $this->send($response);
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @param $route
     * @param null $data
     * @return mixed
     * @throws Exception
     */
    public function resolveRoute($route, $data = null)
    {
        return Mindy::app()->urlManager->reverse($route, $data);
    }

    /**
     * Send response with application/json headers
     * @param $data
     */
    public function json($data)
    {
        $body = !is_string($data) ? json_encode($data) : $data;
        $this->send($this->getResponse()
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(stream_for($body)));
    }

    /**
     * Shortcut for text/html response
     * @param $html
     */
    public function html($html)
    {
        $this->send($this->getResponse()
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/html')
            ->withBody(stream_for($html)));
    }

    /**
     * @return bool
     */
    public function isXhr()
    {
        return $this->getRequest()->isXhr();
    }
}