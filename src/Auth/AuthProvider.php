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

class AuthProvider extends BaseAuthProvider
{
    /**
     * @var int
     */
    public $authTimeout = 2592000;
    /**
     * @var bool
     */
    public $allowAutoLogin = true;
    /**
     * @var bool
     */
    public $destroySessionAfterLogout = false;
    /**
     * @var string
     */
    public $userClass;

    /**
     * @param IUser $user
     * @return bool
     */
    public function login(IUser $user) : bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function logout() : bool
    {
        if ($this->allowAutoLogin) {
            $http = app()->http;
            $http->setResponse($http->getResponse()->withoutCookie('__user'));
        }

        if ($this->destroySessionAfterLogout) {
            app()->http->session->destroy();
        }

        $this->setUser($this->getGuestUser());
        return true;
    }

    protected function getGuestUser() : IUser
    {
        if ($this->userClass === null) {
            throw new Exception('userClass is null');
        }
        return Creator::createObject([
            'class' => $this->userClass
        ]);
    }
}