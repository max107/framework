<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:43
 */

declare(strict_types = 1);

namespace Mindy\Validation;

class ValidatorBuilder
{
    /**
     * @param $value
     * @param array $validators
     * @return array
     */
    public function validate($value, array $validators = []) : array
    {
        $errors = [];
        foreach ($validators as $validator) {
            $errors = array_merge($errors, $validator($value));
        }
        return $errors;
    }
}