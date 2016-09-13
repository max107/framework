<?php

namespace Mindy\Form\Fields;

/**
 * Class TextField
 * @package Mindy\Form\Fields
 */
class TextField extends Field
{
    /**
     * @var string
     */
    public $template = "<input type='text' value='{value}' id='{id}' name='{name}'{html}/>";
}
