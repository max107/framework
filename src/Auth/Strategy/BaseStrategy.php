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
 */
abstract class BaseStrategy implements IAuthStrategy
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
     * BaseStrategy constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else {
                $this->{$key} = $value;
            }
        }
    }

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
    public function setAuthProvider(IAuthProvider $authProvider)
    {
        $this->_auth = $authProvider;
        return $this;
    }

    /**
     * @return IAuthProvider
     */
    public function getAuthProvider() : IAuthProvider
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
    public function getErrors() : array
    {
        return $this->_errors;
    }

    /**
     * @return IUser
     */
    public function getUser() : IUser
    {
        return $this->_user;
    }
}