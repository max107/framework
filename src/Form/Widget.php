<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 11:47
 */

namespace Mindy\Form;

use Exception;
use Mindy\Form\Fields\Field;

abstract class Widget implements WidgetInterface
{
    /**
     * Widget constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param FieldInterface $field
     * @return string
     */
    abstract public function render(FieldInterface $field) : string;
}