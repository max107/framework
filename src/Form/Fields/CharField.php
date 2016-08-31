<?php

namespace Mindy\Form\Fields;

use Mindy\Exception\Exception;

/**
 * Class CharField
 * @package Mindy\Form
 */
class CharField extends Field
{
    public $template = "<input type='{type}' value='{value}' id='{id}' name='{name}'{html}/>";

    public function render()
    {
        $label = $this->renderLabel();
        $input = $this->renderInput();
        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();
        return implode("\n", [$label, $input, $hint, $errors]);
    }

    public function getValue()
    {
        // TODO wtf?
        $value = parent::getValue();
        if ($value) {
            return $value;
        }
        if ($this->value instanceof \Mindy\Orm\Manager) {
            throw new Exception("Value must be a string, not a manager");
        }
        return $this->value;
    }
}
