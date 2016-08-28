<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 18:22
 */

namespace Mindy\Permissions;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Permission
{
    /**
     * @var string
     */
    protected $code;
    /**
     * @var bool
     */
    protected $is_global = false;
    /**
     * @var bool
     */
    protected $biz_rule = false;
    /**
     * @var array
     */
    protected $users = [];
    /**
     * @var array
     */
    protected $groups = [];

    /**
     * Permission constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    public function getCode() : string
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function getIsGlobal() : bool
    {
        return $this->is_global;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function evaluateBizRule(array $params = []) : bool
    {
        $language = new ExpressionLanguage();
        return $language->evaluate($this->biz_rule, $params);
    }

    /**
     * @param int $userId
     * @param array $params
     * @return bool
     */
    public function canUser(int $userId, array $params = []) : bool
    {
        if (in_array($userId, $this->users)) {
            if (empty($this->biz_rule)) {
                return true;
            } else {
                try {
                    return $this->evaluateBizRule($params);
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * @param int $groupId
     * @param array $params
     * @return bool
     */
    public function canGroup(int $groupId, array $params = []) : bool
    {
        if (in_array($groupId, $this->groups)) {
            if (empty($this->biz_rule)) {
                return true;
            } else {
                try {
                    return $this->evaluateBizRule($params);
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }
}
