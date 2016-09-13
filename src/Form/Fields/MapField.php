<?php

namespace Mindy\Form\Fields;

use Mindy\Form\Widget\MapWidget;

/**
 * Class MapField
 * @package Mindy\Form
 */
class MapField extends TextField
{
    public $widget = [
        'class' => MapWidget::class
    ];
}
