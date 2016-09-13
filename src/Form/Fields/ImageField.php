<?php

namespace Mindy\Form\Fields;
use Mindy\Form\Widget\ImageWidget;

/**
 * Class ImageField
 * @package Mindy\Form
 */
class ImageField extends FileField
{
    /**
     * @var string
     */
    public $currentTemplate = '<p class="current-file-container">{label}:<br/><a class="current-file" href="{current}" target="_blank"><img src="{current}" alt="{current}" /></a></p>';
    /**
     * @var array
     */
    public $widget = [
        'class' => ImageWidget::class
    ];
}
