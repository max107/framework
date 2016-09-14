<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 18:04
 */

declare(strict_types = 1);

namespace Mindy\Permissions;

use function Mindy\app;
use Mindy\Auth\UserInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PermissionManager
{
    /**
     * @var array
     */
    private $_permissions = [];

    /**
     * PermissionManager constructor.
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
     * @param $permissions
     */
    public function setPermissions($permissions)
    {
        if ($permissions instanceof \Closure) {
            $permissions = $permissions();
        }

        foreach ($this->fetchPermissions($permissions) as $perm) {
            $this->_permissions[$perm->getCode()] = $perm;
        }
    }

    /**
     * @param array $permissions
     * @return \Generator
     */
    protected function fetchPermissions(array $permissions)
    {
        foreach ($permissions as $perm) {
            yield new Permission($perm);
        }
    }

    /**
     * @param $code
     * @return bool
     */
    public function canGlobal($code) : bool
    {
        if (isset($this->_permissions[$code])) {
            return $this->_permissions[$code]->getIsGlobal();
        }
        return false;
    }

    /**
     * @param $code
     * @return bool
     */
    protected function hasPermission($code) : bool
    {
        return array_key_exists($code, $this->_permissions);
    }

    /**
     * @param $code
     * @return Permission
     */
    protected function getPermission($code) : Permission
    {
        return $this->_permissions[$code];
    }

    /**
     * @param UserInterface $user
     * @param string $code
     * @param array $params
     * @return bool
     */
    public function canUser(UserInterface $user, string $code, array $params = []) : bool
    {
        if ($this->hasPermission($code)) {
            return $this->getPermission($code)->canUser($user->id, $params);
        }
        return false;
    }

    /**
     * @param UserInterface $user
     * @param string $code
     * @param array $params
     * @return bool
     */
    public function canGroup(UserInterface $user, string $code, array $params = []) : bool
    {
        if ($this->hasPermission($code)) {
            foreach ($user->groups as $group) {
                $state = $this->getPermission($code)->canGroup($group->id, $params);
                if ($state === true) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Проверяем данную операцию для пользователя в кеше
     * @param UserInterface $user
     * @param string $code
     * @param array $params
     * @return bool
     */
    public function can(UserInterface $user, string $code, array $params = []) : bool
    {
        /**
         * Пользователю суперадминистратор все разрешено по умолчанию
         */
        if ($user->is_superuser) {
            return true;
        }

        /**
         * Проверяем глобальные правила без учета bizRule.
         * Если пользователю запрещено или у пользователя отсутствовали такой код прав доступа,
         * проверяем права группы и выполняем бизнес правило
         */
        return
            $this->canGlobal($code) ||
            $this->canUser($user, $code, $params) ||
            $this->canGroup($user, $code, $params);
    }
}