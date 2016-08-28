<?php

namespace Mindy\Validation;
use function Mindy\app;

/**
 * Class MinLengthValidator
 * @package Mindy\Validation
 */
class MinLengthValidator extends Validator
{
    public $minLength;

    public function __construct($minLength)
    {
        $this->minLength = $minLength;
    }

    public function validate($value)
    {
        if (is_object($value)) {
            $this->addError(app()->t('validation', "{type} is not a string", ['{type}' => gettype($value)]));
        } else if (mb_strlen((string)$value, 'UTF-8') < $this->minLength) {
            $this->addError(app()->t('validation', "Minimal length is {length}", ['{length}' => $this->minLength]));
        }

        return $this->hasErrors() === false;
    }
}