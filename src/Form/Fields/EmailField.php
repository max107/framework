<?php

namespace Mindy\Form\Fields;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EmailField
 * @package Mindy\Form
 */
class EmailField extends CharField
{
    protected function getValidationConstraints() : array
    {
        $constraints = parent::getValidationConstraints();
        return array_merge($constraints, [
            new Assert\Email()
        ]);
    }
}
