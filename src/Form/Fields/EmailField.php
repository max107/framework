<?php

namespace Mindy\Form\Fields;

use Mindy\Validation\EmailValidator;

/**
 * Class EmailField
 * @package Mindy\Form
 */
class EmailField extends CharField
{
    public function init()
    {
        parent::init();
        $this->validators[] = new EmailValidator($this->required);
    }
}
