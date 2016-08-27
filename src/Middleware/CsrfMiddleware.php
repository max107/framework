<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 01:07
 */

namespace Mindy\Middleware;

use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class CsrfMiddleware
 * @package Mindy\Middleware
 */
class CsrfMiddleware
{
    /**
     * @var array exclude urls from csrf validation
     */
    public $exclude = [];
    /**
     * @var string the name of the token used to prevent CSRF. Defaults to 'YII_CSRF_TOKEN'.
     * This property is effectively only when {@link enableCsrfValidation} is true.
     */
    public $tokenName = 'X-CSRFToken';
    /**
     * @var bool
     */
    public $autoInject = true;
    /**
     * @var string
     */
    private $_csrfToken;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return \Mindy\Http\Response\Response|ResponseInterface|static
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (in_array($request->getMethod(), ['HEAD', 'OPTIONS', 'GET'])) {
            $response = $next($request, $response);

            if (array_key_exists($this->getName(), $request->getCookieParams()) === false) {
                /** @var \Mindy\Http\Response\Response $response */
                $response->withCookie([
                    'name' => $this->getName(),
                    'value' => $this->getValue($request)
                ]);
            }

            return $this->autoInject ? $this->autoInjectCsrf($request, $response) : $response;
        } else {
            foreach ($this->exclude as $pattern) {
                if (preg_match($pattern, $request->getRequestTarget())) {
                    return $next($request, $response);
                }
            }

            if ($this->isValid($request) === false) {
                return $response->withStatus(403);
            }
        }

        return $next($request, $response);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->tokenName;
    }

    /**
     * @return string
     */
    public function renderInput(ServerRequestInterface $request)
    {
        return strtr('<input name="{name}" value="{value}" type="hidden"/>', [
            '{name}' => $this->getName(),
            '{value}' => $this->getValue($request)
        ]);
    }

    /**
     * Returns the random token used to perform CSRF validation.
     * @return string the random token for CSRF validation.
     */
    public function getValue(ServerRequestInterface $request)
    {
        if ($this->_csrfToken === null) {
            if (($csrf = $request->getHeaderLine($this->getName())) !== '') {
                $this->_csrfToken = $csrf;
            } else if (($cookies = $request->getCookieParams()) && isset($cookies[$this->getName()])) {
                $this->_csrfToken = $cookies[$this->getName()];
            } else {
                $this->_csrfToken = $this->createToken();
            }
        }
        return $this->_csrfToken;
    }

    public function createToken()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    public function autoInjectCsrf(ServerRequestInterface $request, ResponseInterface $response)
    {
        $input = $this->renderInput($request);
        $html = preg_replace_callback('/(<form\s[^>]*method=["\']?POST["\']?[^>]*>)/i', function ($match) use ($input) {
            return $match[0] . $input;
        }, (string)$response->getBody(), -1, $count);

        if (!empty($count)) {
            $body = stream_for('php://temp', 'r+');
            $body->write($html);
            return $response->withBody($body);
        }
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isValid(ServerRequestInterface $request)
    {
        return $request->getHeaderLine($this->getName()) == $this->getValue($request);
    }
}