<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 20:42
 */

namespace Mindy\Http;

use Mindy\Base\Mindy;
use Mindy\Exception\HttpException;

/**
 * Class Csrf
 * @package Mindy\Http
 */
class Csrf
{
    /**
     * @var boolean whether to enable CSRF (Cross-Site Request Forgery) validation. Defaults to false.
     * By setting this property to true, forms submitted to an Yii Web application must be originated
     * from the same application. If not, a 400 HTTP exception will be raised.
     * Note, this feature requires that the user client accepts cookie.
     * You also need to use {@link CHtml::form} or {@link CHtml::statefulForm} to generate
     * the needed HTML forms in your pages.
     * @see http://seclab.stanford.edu/websec/csrf/csrf.pdf
     */
    public $enableCsrfValidation = true;
    /**
     * @var string the name of the token used to prevent CSRF. Defaults to 'YII_CSRF_TOKEN'.
     * This property is effectively only when {@link enableCsrfValidation} is true.
     */
    public $csrfTokenName = 'X-CSRFToken';
    /**
     * @var array the property values (in name-value pairs) used to initialize the CSRF cookie.
     * Any property of {@link HttpCookie} may be initialized.
     * This property is effective only when {@link enableCsrfValidation} is true.
     */
    public $csrfCookie;
    /**
     * @var string csrf token value
     */
    private $_csrfToken;
    /**
     * @var Http
     */
    private $http;

    /**
     * Csrf constructor.
     * @param Http $http
     * @param array $config
     */
    public function __construct(Http $http, array $config = [])
    {
        $this->http = $http;
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->csrfTokenName;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->getCsrfToken();
    }

    /**
     * Returns the random token used to perform CSRF validation.
     * The token will be read from cookie first. If not found, a new token
     * will be generated.
     * @return string the random token for CSRF validation.
     * @see enableCsrfValidation
     */
    public function getCsrfToken()
    {
        if ($this->_csrfToken === null) {
            /** @var Cookie $cookie */
            $cookie = $this->http->cookies->get($this->csrfTokenName);
            if (!$cookie || ($this->_csrfToken = $cookie->value) == null) {
                $cookie = $this->createCsrfCookie();
                $this->_csrfToken = $cookie->value;
                $this->http->cookies->set($cookie->name, $cookie);
            }
        }

        return $this->_csrfToken;
    }

    /**
     * Creates a cookie with a randomly generated CSRF token.
     * Initial values specified in {@link csrfCookie} will be applied
     * to the generated cookie.
     * @return Cookie the generated cookie
     * @see enableCsrfValidation
     */
    protected function createCsrfCookie()
    {
        $cookie = new Cookie($this->csrfTokenName, sha1(uniqid(mt_rand(), true)));
        if (is_array($this->csrfCookie)) {
            foreach ($this->csrfCookie as $name => $value) {
                $cookie->$name = $value;
            }
        }
        return $cookie;
    }

    /**
     * Performs the CSRF validation.
     * This is the event handler responding to {@link CApplication::onBeginRequest}.
     * The default implementation will compare the CSRF token obtained
     * from a cookie and from a POST field. If they are different, a CSRF attack is detected.
     * @throws HttpException
     */
    public function getIsValid()
    {
        $userToken = $this->http->getRequest()->getHeaderLine($this->csrfTokenName);
        if (empty($userToken)) {
            $userToken = $this->http->post->get($this->csrfTokenName);
        }

        if (!empty($userToken) && $this->http->cookies->has($this->csrfTokenName)) {
            $cookieToken = $this->http->cookies->get($this->csrfTokenName)->value;
            // https://github.com/studio107/Mindy_Base/issues/1
            $rawData = Mindy::app()->security->validateData($userToken);
            $valid = $cookieToken === $userToken || $cookieToken === @unserialize($rawData);
        } else {
            $valid = false;
        }

        return $valid;
    }

    /**
     * @return string
     */
    public function renderInput()
    {
        return strtr('<input name="{name}" value="{value}" type="hidden"/>', [
            '{name}' => $this->getName(),
            '{value}' => $this->getValue()
        ]);
    }

    public function autoInject($html)
    {
        $input = $this->renderInput();
        return preg_replace_callback('/(<form\s[^>]*method=["\']?POST["\']?[^>]*>)/i', function($match) use ($input) {
            return $match[0].$input;
        }, $html, -1, $count);
    }
}