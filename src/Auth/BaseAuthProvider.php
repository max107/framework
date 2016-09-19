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
use function Mindy\app;
use Mindy\Auth\PasswordHasher\IPasswordHasher;
use Mindy\Auth\Strategy\AuthStrategyInterface;
use Mindy\Creator\Creator;

/**
 * Class BaseAuthProvider
 * @package Mindy\Auth
 */
abstract class BaseAuthProvider implements AuthProviderInterface
{
    /**
     * @var string
     */
    public $cookieName = '__user';
    /**
     * @var string
     */
    public $defaultPasswordHasher = 'mindy';
    /**
     * @var string
     */
    public $userClass;
    /**
     * @var UserInterface
     */
    private $_user;
    /**
     * @var array|\Mindy\Auth\PasswordHasher\IPasswordHasher[]
     */
    private $_passwordHashers = [];
    /**
     * @var array|\Mindy\Auth\Strategy\AuthStrategyInterface[]
     */
    private $_strategies = [];

    /**
     * BaseAuthProvider constructor.
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

        $user = $this->getUserFromSession();
        if ($user === null) {
            $user = $this->getGuestUser();
        }
        $this->setUser($user);

        app()->signal->handler($this, 'onAuth', [$this, 'onAuth']);
    }

    /**
     * @return mixed|null
     */
    protected function getUserFromSession()
    {
        $user = app()->http->session->get($this->cookieName);
        if (isset($user['id'])) {
            $model = Creator::createObject(['class' => $this->userClass]);
            if ($instance = $model->objects()->get(['id' => $user['id']])) {
                return $instance;
            }
        }
        return null;
    }

    /**
     * @param UserInterface $user
     * @return bool
     */
    abstract public function login(UserInterface $user) : bool;

    /**
     * @return bool
     */
    abstract public function logout() : bool;

    /**
     * @param UserInterface $user
     * @return $this
     */
    public function setUser(UserInterface $user)
    {
        $this->_user = $user;
        return $this;
    }

    /**
     * @return UserInterface
     */
    public function getUser() : UserInterface
    {
        return $this->_user;
    }

    /**
     * @return UserInterface
     * @throws Exception
     */
    protected function getGuestUser() : UserInterface
    {
        if ($this->userClass === null) {
            throw new Exception('userClass is null');
        }
        return Creator::createObject([
            'class' => $this->userClass
        ]);
    }

    /**
     * @param array $attributes
     * @return array|bool
     */
    public function authenticate(string $name, array $attributes) : array
    {
        $strategy = $this->getStrategy($name);
        $state = $strategy->process($this->getUser(), $attributes);
        if ($state && $this->login($strategy->getUser())) {
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
            if (is_string($strategy)) {
                $strategy = ['class' => $strategy];
            }

            if (is_array($strategy)) {
                $this->_strategies[$name] = Creator::createObject(array_merge($strategy, ['authProvider' => $this]));
            } else {
                $this->_strategies[$name] = $strategy->setAuthProvider($this);
            }
        }
        return $this;
    }

    /**
     * @return \Mindy\Auth\Strategy\AuthStrategyInterface[]
     */
    public function getStrategies() : array
    {
        return $this->_strategies;
    }

    /**
     * @param $name
     * @return \Mindy\Auth\Strategy\AuthStrategyInterface
     */
    public function getStrategy(string $name) : AuthStrategyInterface
    {
        return $this->_strategies[$name];
    }

    /**
     * @param array $hashers
     */
    public function setPasswordHashers(array $hashers)
    {
        foreach ($hashers as $name => $config) {
            if (is_string($config)) {
                $config = ['class' => $config];
            }
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

        throw new Exception('Unknown password hasher: ' . $hasher);
    }

    /**
     * @param UserInterface $user
     */
    public function onAuth(UserInterface $user)
    {

    }
}