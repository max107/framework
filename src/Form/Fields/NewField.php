<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:54
 */

namespace Mindy\Form\Fields;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

class NewField
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var bool
     */
    protected $required = true;
    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors = [];
    /**
     * @var array
     */
    protected $validators = [];

    /**
     * NewField constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected function getValidator()
    {
        return Validation::createValidatorBuilder()->getValidator();
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    protected function getValidationConstraints() : array
    {
        $constraints = [];
        if ($this->required) {
            $constraints[] = new Assert\NotBlank();
        }
        return $constraints;
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $constraints = array_merge($this->getValidationConstraints(), $this->validators);
        $errors = $this->getValidator()->validate($this->getValue(), $constraints);
        $this->setErrors($errors);
        return count($errors) === 0;
    }

    /**
     * @param ConstraintViolationListInterface $errors
     * @return $this
     */
    protected function setErrors(ConstraintViolationListInterface $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getErrors()
    {
        $errors = [];
        foreach ($this->errors as $key => $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}