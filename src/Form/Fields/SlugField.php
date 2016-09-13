<?php

namespace Mindy\Form\Fields;

/**
 * Class ShortUrlField
 * @package Mindy\Form
 */
class SlugField extends TextField
{
    public function getValue()
    {
        $slugs = explode('/', parent::getValue());
        return end($slugs);
    }
}
