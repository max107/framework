<?php

namespace Mindy\Form\Fields;

/**
 * Class PasswordField
 * @package Mindy\Form
 */
class PasswordField extends Field
{
    /**
     * @var string
     */
    public $template = "<input type='password' value='{value}' id='{id}' name='{name}'{html}/>";
}
