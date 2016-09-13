<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:20
 */

namespace Mindy\Form;

use Mindy\Helper\Creator;

class NewForm implements FormInterface, \ArrayAccess
{
    protected $errors = [];
    
    protected $fields = [];

    public function __construct()
    {
        foreach ($this->getFields() as $name => $field) {
            $this->fields[$name] = Creator::createObject($field);
        }
    }

    public function getFields()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $errors = [];
        foreach ($this->fields as $name => $field) {
            $field->isValid();
            $errors[$name] = $field->getErrors();
        }
        $this->setErrors($errors);
        return count($errors) == 0;
    }

    /**
     * @param array $errors
     * @return $this
     */
    protected function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }

    public function getField($name)
    {
        return $this->fields[$name];
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->hasField($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->getField($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->getField($offset)->setValue($value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
    }
}