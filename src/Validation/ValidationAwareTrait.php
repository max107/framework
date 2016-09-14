<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 17:35
 */

namespace Mindy\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

trait ValidationAwareTrait
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors = [];

    /**
     * @return array
     */
    protected function getValidationConstraints() : array
    {
        return [];
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected function getValidator()
    {
        return Validation::createValidatorBuilder()->getValidator();
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $errors = $this->getValidator()->validate($this->getValue(), $this->getValidationConstraints());
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
     * @return array
     */
    public function getErrors() : array
    {
        $errors = [];
        foreach ($this->errors as $key => $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}