<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 20:29
 */

namespace Mindy\Form;

interface WidgetInterface
{
    /**
     * @param FormInterface $form
     * @param FieldInterface $field
     * @return string
     */
    public function render(FormInterface $form, FieldInterface $field) : string;
}