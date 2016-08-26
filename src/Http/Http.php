<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 23:04
 */

declare(strict_types = 1);

namespace Mindy\Http;

use Exception;
use function GuzzleHttp\Psr7\stream_for;
use Mindy\Base\Mindy;
use Mindy\Helper\Json;
use Mindy\Helper\ReadOnlyCollection;
use Mindy\Helper\Traits\Configurator;
use Mindy\Http\Response\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Http
 * @package Mindy\Http
 */
class Http
{
    use Configurator;
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
     * @var ReadOnlyCollection
     */
    public $get;
    /**
     * @var ReadOnlyCollection
     */
    public $post;
    /**
     * @var ReadOnlyCollection
     */
    public $files;
    /**
     * @var ReadOnlyCollection
     */
    public $cookies;

    /**
     * Http constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->request = Request::fromGlobals();

        $this->cookies = new ReadOnlyCollection($this->getRequest()->getCookieParams());
        $this->get = new ReadOnlyCollection($this->getRequest()->getQueryParams());
        $this->post = new ReadOnlyCollection($this->getRequest()->getServerParams());
        $this->files = new ReadOnlyCollection($this->getRequest()->getUploadedFiles());

        $this->response = $this->withMiddleware($this->request, new Response());

        if (isRedirectResponse($this->response)) {
            $this->send($this->response);
        }
    }

    /**
     * @return Request|\Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
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
        sendResponse($this->withMiddleware($this->getRequest(), $response), $this->getSettings());
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
    public function json($data, $status = 200)
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
     */
    public function html($html, $status = 200)
    {
        return $this->getResponse()
            ->withStatus($status)
            ->withHeader('Content-Type', 'text/html')
            ->withBody(stream_for($html));
    }

    /**
     * @return bool
     */
    public function isXhr()
    {
        return $this->getRequest()->isXhr();
    }
}