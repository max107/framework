<?php

namespace Mindy\Form\Fields;

/**
 * Class DateTimeField
 * @package Mindy\Form
 */
class DateTimeField extends TextField
{
    /**
     * @var string
     */
    public $template = "<input type='datetime' value='{value}' id='{id}' name='{name}'{html}/>";
}
