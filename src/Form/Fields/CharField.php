<?php

namespace Mindy\Form\Fields;

/**
 * Class CharField
 * @package Mindy\Form
 */
class CharField extends NewField
{
    public $template = "<input type='{type}' value='{value}' id='{id}' name='{name}'{html}/>";
}
