<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:07
 */

namespace Mindy\Auth;

use Exception;
use function Mindy\app;
use Mindy\Helper\Creator;
use Modules\User\Models\Session;

class AuthProvider extends BaseAuthProvider
{
    /**
     * @var int
     */
    public $authTimeout = 2592000;
    /**
     * @var bool
     */
    public $autoLogin = true;
    /**
     * @var bool
     */
    public $destroySessionAfterLogout = false;
    /**
     * @var string
     */
    private $_keyPrefix;

    /**
     * @return string a prefix for the name of the session variables storing user session data.
     */
    public function getStateKeyPrefix()
    {
        if ($this->_keyPrefix === null) {
            $this->_keyPrefix = md5(get_class($this) . '.' . app()->getId());
        }
        return $this->_keyPrefix;
    }

    /**
     * @param IUser $user
     * @return bool
     */
    public function login(IUser $user) : bool
    {
        if ($user->isGuest()) {
            return false;
        }

        $this->setUser($user);
        $session = app()->http->session;

//        $model->last_login = $model->getDb()->getAdapter()->getDateTime();
//        $model->save(['last_login']);
//        $this->saveToCookie($user, $this->authTimeout);
//        $session->set($this->getStateKeyPrefix(), 1);
//        if ($this->absoluteAuthTimeout) {
//            $this->getStorage()->add(self::AUTH_ABSOLUTE_TIMEOUT_VAR, time() + $this->absoluteAuthTimeout);
//        }

        $session = Session::objects()->get(['id' => $session->getId()]);
        if ($session) {
            $session->user = $user;
            $session->save(['user']);
        }

        app()->signal->send($this, 'onAuth', $user);

        return true;
    }

    /**
     * @return bool
     */
    public function logout() : bool
    {
        if ($this->autoLogin) {
            $http = app()->http;
            $response = $http->getResponse()->withoutCookie($this->cookieName);
            $http->setResponse($response);
        }

        if ($this->destroySessionAfterLogout) {
            $session = app()->http->session;
            $session->destroy($session->getId());
        }

        $this->setUser($this->getGuestUser());
        return true;
    }
}