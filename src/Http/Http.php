<?php

declare(strict_types = 1);

namespace Mindy\Http;

use Exception;
use function GuzzleHttp\Psr7\stream_for;
use Mindy\Base\Mindy;
use Mindy\Creator\Creator;
use Mindy\Helper\Json;
use Mindy\Helper\Traits\Accessors;
use Mindy\Http\Collection\CookieParamCollection;
use Mindy\Http\Collection\FileParamCollection;
use Mindy\Http\Collection\GetParamCollection;
use Mindy\Http\Collection\PostParamCollection;
use Mindy\Http\Response\Response;
use Mindy\Middleware\MiddlewareManager;
use Mindy\Session\Flash;
use Mindy\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Http
 * @package Mindy\Http
 * @property \Mindy\Session\Adapter\SessionHandlerInterface $session The session component.
 */
class Http
{
    use Accessors;
    use Legacy;

    /**
     * @var array
     */
    public $settings = [];
    /**
     * @var array
     */
    protected $defaultSettings = [
        'responseChunkSize' => 4096,
    ];
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
     * @var GetParamCollection
     */
    public $get;
    /**
     * @var PostParamCollection
     */
    public $post;
    /**
     * @var FileParamCollection
     */
    public $files;
    /**
     * @var CookieParamCollection
     */
    public $cookies;
    /**
     * @var Flash
     */
    public $flash;
    /**
     * @var callable
     */
    private $_middleware;
    /**
     * @var Session
     */
    protected $session;

    /**
     * Http constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);

        $this->request = Request::fromGlobals();

        $this->cookies = new CookieParamCollection($this->request);
        $this->get = new GetParamCollection($this->request);
        $this->post = new PostParamCollection($this->request);
        $this->files = new FileParamCollection($this->request);

        $this->flash = new Flash();

        $this->response = $this->withMiddleware($this->request, new Response());
        if (isRedirectResponse($this->response)) {
            $this->send($this->response);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;

        $this->cookies = new CookieParamCollection($this->request);
        $this->get = new GetParamCollection($this->request);
        $this->post = new PostParamCollection($this->request);
        $this->files = new FileParamCollection($this->request);

        return $this;
    }

    /**
     * @param array $config
     */
    protected function configure(array $config)
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else {
                $this->{$key} = $value;
            }
        }
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getSession() : Session
    {
        return $this->session;
    }

    /**
     * @param Session $session
     */
    public function setSession($session)
    {
        if (($session instanceof Session) === false) {
            $session = Creator::createObject($session);
        }
        $this->session = $session;
    }

    /**
     * @param array $middleware
     * @return callable|MiddlewareManager
     */
    public function setMiddleware(array $middleware = [])
    {
        if ($this->_middleware === null) {
            $this->_middleware = new MiddlewareManager($middleware);
        }
        return $this->_middleware;
    }

    /**
     * @return Request|\Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
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
        sendResponse($this->withMiddleware($this->getRequest(), $response), $this->getSettings());
        Mindy::app()->end();
    }

    /**
     * Apply middlewares to response
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function withMiddleware(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->_middleware === null) {
            return $response;
        }

        $middleware = $this->_middleware;
        return $middleware($request, $response);
    }

    /**
     * Return request global settings
     * @return array
     */
    public function getSettings() : array
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
        } else if (is_string($url) && strpos($url, 'http') === false && strpos($url, ':') !== false) {
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
    public function getResponse() : ResponseInterface
    {
        return $this->response;
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
     * @return ResponseInterface
     */
    public function json($data, $status = 200) : ResponseInterface
    {
        $body = !is_string($data) ? Json::encode($data) : $data;
        return $this->getResponse()
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(stream_for($body));
    }

    /**
     * Shortcut for text/html response
     * @param $html
     * @return Response
     */
    public function html($html, $status = 200) : Response
    {
        return $this->getResponse()
            ->withStatus($status)
            ->withHeader('Content-Type', 'text/html')
            ->withBody(stream_for($html));
    }

    /**
     * @return bool
     */
    public function isXhr() : bool
    {
        return $this->getRequest()->isXhr();
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->getRequest()->getRequestTarget();
    }
}