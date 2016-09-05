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
