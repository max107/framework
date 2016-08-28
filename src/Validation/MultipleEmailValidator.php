<?php

namespace Mindy\Validation;
use function Mindy\app;

/**
 * Class MultipleEmailValidator
 * @package Mindy\Validation
 */
class MultipleEmailValidator extends Validator
{
    /**
     * @param $value
     * @return mixed
     */
    public function validate($value)
    {
        $emails = explode(',', $value);
        $validator = new EmailValidator();
        foreach ($emails as $email) {
            if (!empty($email) && !$validator->validate(trim($email))) {
                $this->addError(app()->t('validation', "{email} is not a valid email address", [
                    '{email}' => $email
                ]));
            }
        }
        return $this->hasErrors() === false;
    }
}
