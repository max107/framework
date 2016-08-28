<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 01:44
 */

declare(strict_types = 1);

namespace Mindy\Auth;

use Exception;
use Mindy\Auth\PasswordHasher\IPasswordHasher;
use Mindy\Auth\Strategy\IAuthStrategy;
use Mindy\Helper\Creator;

/**
 * Class BaseAuthProvider
 * @package Mindy\Auth
 */
abstract class BaseAuthProvider implements IAuthProvider
{
    /**
     * @var IUser
     */
    private $_user;
    /**
     * @var array|\Mindy\Auth\PasswordHasher\IPasswordHasher[]
     */
    private $_passwordHashers = [];
    /**
     * @var array|\Mindy\Auth\Strategy\IAuthStrategy[]
     */
    private $_strategies = [];

    /**
     * @param IUser $user
     * @return bool
     */
    abstract public function login(IUser $user) : bool;

    /**
     * @return bool
     */
    abstract public function logout() : bool;

    /**
     * @param IUser $user
     * @return $this
     */
    public function setUser(IUser $user)
    {
        $this->_user = $user;
        return $this;
    }

    /**
     * @return IUser
     */
    public function getUser() : IUser
    {
        return $this->_user;
    }

    /**
     * @param array $attributes
     * @return array|bool
     */
    public function authenticate(string $name, array $attributes) : array
    {
        $strategy = $this->getStrategy($name);
        if ($strategy->process($this->getUser(), $attributes) && $this->login($strategy->getUser())) {
            return [];
        } else {
            return $strategy->getErrors();
        }
    }

    /**
     * @param array $strategies
     * @return $this
     */
    public function setStrategies(array $strategies)
    {
        foreach ($strategies as $name => $strategy) {
            if (is_array($strategy)) {
                $this->_strategies[$name] = Creator::createObject($strategy);
            } else {
                $this->_strategies[$name] = $strategy;
            }
        }
        return $this;
    }

    /**
     * @return \Mindy\Auth\Strategy\IAuthStrategy[]
     */
    public function getStrategies() : array
    {
        return $this->_strategies;
    }

    /**
     * @param $name
     * @return \Mindy\Auth\Strategy\IAuthStrategy
     */
    public function getStrategy($name) : IAuthStrategy
    {
        return $this->_strategies[$name];
    }

    /**
     * @param array $hashers
     */
    public function setPasswordHashers(array $hashers)
    {
        foreach ($hashers as $name => $config) {
            $hasher = is_array($config) ? Creator::createObject($config) : $config;
            $this->_passwordHashers[$name] = $hasher;
        }
    }

    /**
     * @param string $hasher
     * @return IPasswordHasher
     * @throws Exception
     */
    public function getPasswordHasher(string $hasher) : IPasswordHasher
    {
        if (isset($this->_passwordHashers[$hasher])) {
            return $this->_passwordHashers[$hasher];
        }

        throw new Exception('Unknown password hasher');
    }
}