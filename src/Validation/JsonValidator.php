<?php

namespace Mindy\Validation;

use function Mindy\app;
use Mindy\Interfaces\Arrayable;

/**
 * Class JsonValidator
 * @package Mindy\Validation
 */
class JsonValidator extends Validator
{
    public function validate($value)
    {
        if (is_object($value) && !$value instanceof Arrayable) {
            $this->addError(app()->t("validator", "Not json serialize object: {type}", [
                '{type}' => gettype($value)
            ]));
        }

        return $this->hasErrors() === false;
    }
}