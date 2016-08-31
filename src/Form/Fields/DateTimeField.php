<?php

namespace Mindy\Form\Fields;

use Mindy\Form\Widget\DateTimeWidget;

/**
 * Class DateTimeField
 * @package Mindy\Form
 */
class DateTimeField extends DateField
{
    public function init()
    {
        parent::init();
        if (empty($this->widget)) {
            $this->widget = array_merge([
                'class' => DateTimeWidget::class,
                'showTime' => true,
            ], $this->options);
        }
    }

    public function render()
    {
        return parent::render();
    }
}
