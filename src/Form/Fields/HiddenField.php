<?php

namespace Mindy\Form\Fields;

/**
 * Class HiddenField
 * @package Mindy\Form
 */
class HiddenField extends Field
{
    /**
     * @var string
     */
    public $template = "<input type='text' value='{value}' id='{id}' name='{name}'{html}/>";

    public function renderLabel() : string
    {
        return '';
    }

    public function renderErrors() : string
    {
        return '';
    }

    public function renderHint() : string
    {
        return '';
    }
}
