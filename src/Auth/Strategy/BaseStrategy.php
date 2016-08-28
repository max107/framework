<?php

/**
 * Author: Falaleev Maxim (max107)
 * Email: <max@studio107.ru>
 * Company: Studio107 <http://studio107.ru>
 * Date: 11/05/16 12:02
 */
namespace Mindy\Auth\Strategy;

use Mindy\Auth\IAuthProvider;
use Mindy\Auth\IUser;

/**
 * Class BaseStrategy
 * @package Modules\Auth\Strategy
 * @method process \Modules\Auth\Strategy\BaseStrategy
 */
abstract class BaseStrategy
{
    /**
     * @var array
     */
    private $_errors = [];
    /**
     * @var IAuthProvider
     */
    private $_auth;
    /**
     * @var IUser
     */
    private $_user;

    /**
     * @param IUser $user
     */
    protected function setUser(IUser $user)
    {
        $this->_user = $user;
    }

    /**
     * @param IAuthProvider $authProvider
     * @return $this
     */
    protected function setAuthProvider(IAuthProvider $authProvider)
    {
        $this->_auth = $authProvider;
        return $this;
    }

    /**
     * @return IAuthProvider
     */
    protected function getAuthProvider() : IAuthProvider
    {
        return $this->_auth;
    }

    /**
     * @param IUser $user
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function verifyPassword(IUser $user, $password)
    {
        $hash = $user->password;
        $hasher = $this->getAuthProvider()->getPasswordHasher($user->hash_type);
        return $hasher->verifyPassword($password, $hash);
    }

    /**
     * @param $attribute
     * @param $error
     */
    public function addError($attribute, $error)
    {
        if (!isset($this->_errors[$attribute])) {
            $this->_errors[$attribute] = [];
        }
        $this->_errors[$attribute][] = $error;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }
}