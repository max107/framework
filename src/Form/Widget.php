<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 11:47
 */

namespace Mindy\Form;

abstract class Widget implements WidgetInterface
{
    /**
     * Widget constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}