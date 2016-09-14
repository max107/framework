<?php

/**
 * Author: Falaleev Maxim (max107)
 * Email: <max@studio107.ru>
 * Company: Studio107 <http://studio107.ru>
 * Date: 11/05/16 12:02
 */
namespace Mindy\Auth\Strategy;

use Mindy\Auth\AuthProviderInterface;
use Mindy\Auth\IUser;
use Mindy\Auth\UserProvider\UserProviderInterface;

/**
 * Class BaseStrategy
 * @package Modules\Auth\Strategy
 */
abstract class BaseStrategy implements AuthStrategyInterface
{
    /**
     * @var array
     */
    private $_errors = [];
    /**
     * @var IUser
     */
    private $_user;
    /**
     * @var AuthProviderInterface
     */
    protected $authProvider;
    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * BaseStrategy constructor.
     * @param AuthProviderInterface $authProvider
     * @param UserProviderInterface $userProvider
     */
    public function __construct(AuthProviderInterface $authProvider, UserProviderInterface $userProvider)
    {
        $this->authProvider = $authProvider;
        $this->userProvider = $userProvider;
    }

    /**
     * @param IUser $user
     */
    protected function setUser(IUser $user)
    {
        $this->_user = $user;
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
        return $this->authProvider
            ->getPasswordHasher($user->hash_type)
            ->verifyPassword($password, $hash);
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