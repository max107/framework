<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 20:09
 */

declare(strict_types = 1);

namespace Mindy\Form;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use Mindy\Helper\Creator;
use ReflectionClass;
use ArrayAccess;
use Traversable;

class BaseForm implements FormInterface, ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var array
     */
    public static $ids = [];
    /**
     * @var int
     */
    private $_id;
    /**
     * @var array
     */
    protected $errors = [];
    /**
     * @var FieldInterface[]
     */
    protected $fields = [];

    /**
     * NewBaseForm constructor
     */
    public function __construct()
    {
        foreach ($this->getFields() as $name => $config) {
            $field = Creator::createObject($config);
            $field->setForm($this);
            $field->setName($name);
            $this->fields[$name] = $field;
        }
    }

    /**
     * @param $name
     * @return FieldInterface
     */
    public function __get($name)
    {
        if ($this->hasField($name)) {
            return $this->getField($name);
        } else {
            return $this->{$name};
        }
    }

    /**
     * Clone magic method
     */
    public function __clone()
    {
        $this->_id = null;

        foreach ($this->fields as $name => $field) {
            $this->fields[$name] = clone $field;
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if ($this->hasField($name)) {
            $this->getField($name)->setValue($value);
        }
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $errors = [];
        foreach ($this->fields as $name => $field) {
            if ($field->isValid() === false) {
                $errors[$name] = $field->getErrors();
            }
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

    /**
     * @param array $data
     * @param array $files
     * @return $this
     */
    public function populate(array $data, array $files = [])
    {
        $name = $this->classNameShort();
        if (isset($data[$name])) {
            $this->setAttributes($data[$name]);
        }
        if (isset($files[$name])) {
            $this->setAttributes($files[$name]);
        }
        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $this->fields)) {
                $this->fields[$key]->setValue($value);
            }
        }
        return $this;
    }

    public function getAttributes()
    {
        $attributes = [];
        foreach ($this->fields as $name => $field) {
            $attributes[$name] = $field->getValue();
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function getFields() : array
    {
        return [];
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        if ($this->_id === null) {
            if (!array_key_exists(self::class, self::$ids)) {
                self::$ids[self::class] = 0;
            }

            self::$ids[self::class]++;
            $this->_id = self::$ids[self::class];
        }

        return $this->_id;
    }

    /**
     * @return string
     */
    public function classNameShort() : string
    {
        return (new ReflectionClass(get_called_class()))->getShortName();
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasField($name) : bool
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * @param string $name
     * @return FieldInterface
     */
    public function getField(string $name) : FieldInterface
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
    public function offsetExists($offset) : bool
    {
        return $this->hasField($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return FieldInterface
     * @since 5.0.0
     */
    public function offsetGet($offset) : FieldInterface
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
        throw new LogicException('Method not supported on created forms');
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->fields);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->fields);
    }
}