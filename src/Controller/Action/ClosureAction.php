<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/08/16
 * Time: 14:18
 */

namespace Mindy\Controller\Action;

use ReflectionFunction;

class ClosureAction implements IAction
{
    /**
     * @var callable
     */
    protected $action;

    /**
     * ClosureAction constructor.
     * @param callable $action
     */
    public function __construct(callable $action)
    {
        $this->action = $action;
    }

    /**
     * @param ReflectionFunction $method
     * @param array $params
     * @return bool|mixed
     */
    protected function runWithParamsInternal(ReflectionFunction $method, array $params)
    {
        $ps = [];
        foreach ($method->getParameters() as $i => $param) {
            $name = $param->getName();
            if (isset($params[$name])) {
                if ($param->isArray()) {
                    $ps[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                } elseif (!is_array($params[$name])) {
                    $ps[] = $params[$name];
                } else {
                    return false;
                }
            } elseif ($param->isDefaultValueAvailable()) {
                $ps[] = $param->getDefaultValue();
            } else {
                return false;
            }
        }
        return $method->invokeArgs($ps);
    }

    /**
     * @param array $params
     * @return bool|mixed
     */
    public function runInternal(array $params = [])
    {
        $method = new ReflectionFunction($this->action);
        if ($method->getNumberOfParameters() > 0) {
            return $this->runWithParamsInternal($method, $params);
        } else {
            return $method->invoke();
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'Closure';
    }
}