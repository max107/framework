<?php

namespace Mindy\Validation;

use function Mindy\app;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class JsonValidator
 * @package Mindy\Validation
 */
class JsonValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (
            is_object($value) &&
            method_exists($value, 'toJson') === false &&
            method_exists($value, 'toArray') === false
        ) {
            $this->context->addViolation($constraint->message, ['%type%' => gettype($value)]);
        }
    }
}