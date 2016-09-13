<?php

namespace Mindy\Form\Fields;

/**
 * Class DateField
 * @package Mindy\Form
 */
class DateField extends TextField
{
    /**
     * @var string
     */
    public $template = "<input type='date' value='{value}' id='{id}' name='{name}'{html}/>";
}
