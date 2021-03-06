<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 16:00
 */

namespace Mindy\Auth\Strategy;

use function Mindy\app;
use Mindy\Auth\UserInterface;

/**
 * Class LocalStrategy
 * @package Mindy\Auth\Strategy
 */
class LocalStrategy extends BaseStrategy
{
    /**
     * Разрешение пользователю авторизоваться с не активированным аккаунтом
     * @var bool
     */
    public $allowInactive = true;

    /**
     * @param UserInterface $user
     * @param array $attributes
     * @return bool
     */
    public function process(UserInterface $user, array $attributes) : bool
    {
        $name = $attributes['username'];
        $password = $attributes['password'];

        $attribute = strpos($name, "@") > -1 ? 'email' : 'username';
        /** @var null|array $instance */
        $instance = $this->authProvider->getUserProvider()->get([$attribute => strtolower($name)]);

        if ($instance === null) {
            $this->addError($attribute, app()->t('framework.auth', 'User not registered'));
            return false;
        } else {
            if ($this->verifyPassword($instance, $password)) {
                if ($instance->is_active || !$instance->is_active && $this->allowInactive) {
                    $this->setUser($instance);
                    return true;
                } else {
                    $this->addError($attribute, app()->t('framework.auth', 'Account is not verified'));
                    return false;
                }
            } else {
                $this->addError('password', app()->t('framework.auth', 'Wrong password'));
                return false;
            }
        }
    }
}