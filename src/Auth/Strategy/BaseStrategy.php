<?php

/**
 * Author: Falaleev Maxim (max107)
 * Email: <max@studio107.ru>
 * Company: Studio107 <http://studio107.ru>
 * Date: 11/05/16 12:02
 */
namespace Mindy\Auth\Strategy;

use Mindy\Auth\AuthProviderInterface;
use Mindy\Auth\UserInterface;
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
     * @var UserInterface
     */
    private $_user;
    /**
     * @var AuthProviderInterface
     */
    protected $authProvider;

    /**
     * BaseStrategy constructor.
     * @param AuthProviderInterface $authProvider
     */
    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    /**
     * @param UserInterface $user
     */
    protected function setUser(UserInterface $user)
    {
        $this->_user = $user;
    }

    /**
     * @param UserInterface $user
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function verifyPassword(UserInterface $user, $password)
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
     * @return UserInterface
     */
    public function getUser() : UserInterface
    {
        return $this->_user;
    }
}