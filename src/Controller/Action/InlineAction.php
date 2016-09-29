<?php

namespace Mindy\Controller\Action;

use ReflectionMethod;

/**
 * Class InlineAction
 * @package Mindy\Controller\Action
 */
class InlineAction extends Action
{
    /**
     * Runs the action.
     * The action method defined in the controller is invoked.
     * This method is required by {@link CAction}.
     * @param array $params
     * @return bool
     */
    public function runInternal(array $params = [])
    {
        $methodName = $this->getId();
        $method = new ReflectionMethod($this->classObject, $methodName);
        if ($method->getNumberOfParameters() > 0) {
            return $method->invokeArgs($this->classObject, $params);
        } else {
            return $method->invoke($this->classObject);
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
