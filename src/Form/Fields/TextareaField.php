<?php

namespace Mindy\Form\Fields;

/**
 * Class TextAreaField
 * @package Mindy\Form
 */
class TextareaField extends Field
{
    /**
     * @var string
     */
    public $template = "<textarea id='{id}' name='{name}'{html}>{value}</textarea>";
}
