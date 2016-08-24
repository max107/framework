<?php

namespace Mindy\Controller\Action;
use ReflectionMethod;

/**
 * Class Action
 * @package Mindy\Controller\Action
 * @method run
 */
abstract class Action implements IAction
{
    /**
     * @var mixed
     */
    protected $classObject;
    /**
     * @var string
     */
    protected $id;

    /**
     * InlineAction constructor.
     * @param $classObject mixed
     * @param $id string
     */
    public function __construct($classObject, $id)
    {
        $this->classObject = $classObject;
        $this->id = $id;
    }

    /**
     * Runs the action.
     * The action method defined in the controller is invoked.
     * This method is required by {@link CAction}.
     * @param array $params
     * @return bool
     */
    public function runInternal(array $params = [])
    {
        $method = new ReflectionMethod($this, 'run');
        if ($method->getNumberOfParameters() > 0) {
            return $this->runWithParamsInternal($this, $method, $params);
        } else {
            return $this->run();
        }
    }

    /**
     * @param $object
     * @param ReflectionMethod $method
     * @param $params
     * @return bool|mixed
     */
    protected function runWithParamsInternal($object, ReflectionMethod $method, $params)
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
        return $method->invokeArgs($object, $ps);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return get_class($this) . ':run (' . $this->id . ')';
    }
}
