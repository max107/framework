<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/09/16
 * Time: 11:12
 */

namespace Mindy\Permissions;

use function Mindy\app;
use Mindy\Auth\UserInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class Rule
 * @package Mindy\Permissions
 */
class Rule
{
    /**
     * @var array
     */
    protected $users = [];
    /**
     * @var array
     */
    public $groups = [];
    /**
     * @var array IP patterns.
     */
    public $ips = [];
    /**
     * @var string
     */
    protected $expression;
    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * Rule constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param UserInterface $user
     * @param null $ip
     * @return int
     */
    public function can(UserInterface $user, $ip = null)
    {
        return
            $this->isUserMatched($user) &&
            $this->isGroupMatched($user) &&
            $this->isIpMatched($ip) &&
            $this->isExpressionMatched($user);
    }

    /**
     * @param UserInterface $user the user
     * @return boolean whether the rule applies to the user
     */
    protected function isUserMatched(UserInterface $user)
    {
        $users = $this->users;
        if (empty($users)) {
            return true;
        }

        if (!is_array($users)) {
            $users = [$users];
        }

        foreach ($users as $u) {
            if ($u === '*') {
                return true;
            } else if ($u === '?' && $user->isGuest()) {
                return true;
            } else if ($u === '@' && !$user->isGuest()) {
                return true;
            } else if (!strcasecmp($u, $user->username)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param UserInterface $user the user object
     * @return boolean whether the rule applies to the role
     */
    protected function isGroupMatched($user)
    {
        $groups = $this->groups;

        if (empty($groups)) {
            return true;
        }

        if (!is_array($groups)) {
            $groups = [$groups];
        }

        foreach ($user->groups as $group) {
            if (in_array($group->name, $groups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $ip the IP address
     * @return boolean whether the rule applies to the IP address
     */
    protected function isIpMatched($ip)
    {
        if (empty($this->ips)) {
            return true;
        }
        foreach ($this->ips as $rule) {
            if (
                $rule === '*' ||
                $rule === $ip ||
                (($pos = strpos($rule, '*')) !== false && !strncmp($ip, $rule, $pos))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param UserInterface $user
     * @return bool the expression value. True if the expression is not specified.
     */
    protected function isExpressionMatched(UserInterface $user)
    {
        if ($this->expression === null) {
            return true;
        } else {
            return (new ExpressionLanguage())->evaluate($this->expression, ['user' => $user]);
        }
    }
}