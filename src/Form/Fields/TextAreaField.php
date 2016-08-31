<?php

namespace Mindy\Form\Fields;

/**
 * Class TextAreaField
 * @package Mindy\Form
 */
class TextAreaField extends Field
{
    public $template = "<textarea id='{id}' name='{name}'{html}>{value}</textarea>";
}
