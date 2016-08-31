<?php

namespace Mindy\Form\Fields;

/**
 * Class ImageField
 * @package Mindy\Form
 */
class ImageField extends FileField
{
    public $currentTemplate = '<p class="current-file-container">{label}:<br/><a class="current-file" href="{current}" target="_blank"><img src="{current}" alt="{current}" /></a></p>';
}
