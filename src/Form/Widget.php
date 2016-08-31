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

abstract class Widget
{
    /**
     * @var Field
     */
    private $_field;

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
     * @return Field
     */
    protected function getField()
    {
        return $this->_field;
    }

    /**
     * @param Field $field
     * @return $this
     */
    public function setField(Field $field)
    {
        $this->_field = $field;
        return $this;
    }

    /**
     * @return string
     */
    abstract public function render();
}