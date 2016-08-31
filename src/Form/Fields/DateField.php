<?php

namespace Mindy\Form\Fields;

use Mindy\Form\Widget\DateTimeWidget;
use Mindy\Helper\JavaScript;
use Mindy\Helper\JavaScriptExpression;

/**
 * Class DateField
 * @package Mindy\Form
 */
class DateField extends CharField
{
    public $options = [];

    public function init()
    {
        parent::init();
        if (empty($this->widget)) {
            $this->widget = array_merge([
                'class' => DateTimeWidget::class,
                'showTime' => false,
            ], $this->options);
        }
    }

    public function render()
    {
        $out = parent::render();
        $jsOptions = JavaScript::encode(array_merge($this->options, [
            'field' => new JavaScriptExpression('document.getElementById("' . $this->getHtmlId() . '")')
        ]));
        $js = "<script type='text/javascript'>new Pikaday($jsOptions);</script>";
        return $out . $js;
    }
}
