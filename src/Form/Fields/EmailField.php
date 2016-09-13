<?php

namespace Mindy\Form\Fields;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EmailField
 * @package Mindy\Form
 */
class EmailField extends TextField
{
    public $template = "<input type='email' value='{value}' id='{id}' name='{name}'{html}/>";

    protected function getValidationConstraints() : array
    {
        $constraints = parent::getValidationConstraints();
        return array_merge($constraints, [
            new Assert\Email()
        ]);
    }
}
