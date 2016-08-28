<?php

namespace Mindy\Validation\Traits;

use Closure;
use Mindy\Validation\Validator;

/**
 * Class ValidateField
 * @package Mindy\Validation
 */
trait ValidateField
{
    /**
     * @var \Mindy\Validation\Validator[]
     */
    public $validators = [];
    /**
     * @var array of errors
     */
    private $_errors = [];

    public function clearErrors()
    {
        $this->_errors = [];
    }

    public function getValidators()
    {
        return $this->validators;
    }

    public function isValid()
    {
        /** @var $this \Mindy\Form\Fields\Field|\Mindy\Orm\Fields\Field */

        $this->clearErrors();

        $value = $this->getValue();
        $validators = $this->getValidators();
        foreach ($validators as $validator) {
            if ($validator instanceof Closure) {
                /* @var $validator Closure */
                /* @var $this \Mindy\Validation\Interfaces\IValidateObject */
                $valid = $validator->__invoke($this->getValue());
                if ($valid !== true) {
                    if (!is_array($valid)) {
                        $valid = [$valid];
                    }

                    $this->addErrors($valid);
                }
            } else if ($validator instanceof Validator) {
                if ($this instanceof \Mindy\Form\Fields\Field && $this->getForm() instanceof \Mindy\Form\ModelForm) {
                    $instance = $this->form->getInstance();
                    if (!$instance) {
                        $instance = $this->form->getModel();
                    }
                    $validator->setModel($instance);
                }
                $validator->clearErrors();
                if ($validator->validate($value) === false) {
                    $this->addErrors($validator->getErrors());
                }
            }
        }

        return $this->hasErrors() === false;
    }

    public function getErrors()
    {
        return array_unique($this->_errors);
    }

    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    public function addErrors($errors)
    {
        $this->_errors = array_merge($this->_errors, $errors);
    }

    public function addError($error)
    {
        $this->_errors[] = $error;
    }
}
