<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 19:39
 */

namespace Mindy\Orm;

use Exception;
use ArrayAccess;
use Mindy\Orm\Fields\AutoField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Fields\ModelFieldInterface;

/**
 * Class NewBase
 * @package Mindy\Orm
 * @method static \Mindy\Orm\Manager objects($instance = null)
 */
abstract class NewBase implements ModelInterface, ArrayAccess
{
    /**
     * @var array
     */
    protected $errors = [];
    /**
     * @var bool
     */
    protected $isNewRecord = true;
    /**
     * @var AttributeCollection
     */
    protected $attributes;

    /**
     * NewOrm constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        self::getMeta();

        $this->attributes = new AttributeCollection;
        $this->setAttributes($attributes);
    }

    /**
     * @param $name
     * @return string
     */
    protected function convertToPrimaryKeyName($name) : string
    {
        return $name == 'pk' ? $this->getPrimaryKeyName() : $name;
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        $name = $this->convertToPrimaryKeyName($name);
        if ($this->hasField($name)) {
            $this->setAttribute($name, $value);
        } else {
            throw new Exception("Setting unknown property " . get_class($this) . "::" . $name);
        }
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        $name = $this->convertToPrimaryKeyName($name);
        if ($this->hasAttribute($name)) {
            $this->setAttribute($name, null);
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        $name = $this->convertToPrimaryKeyName($name);
        if ($this->hasField($name)) {
            return $this->getFieldValue($name);
        } else {
            throw new Exception("Setting unknown property " . get_class($this) . "::" . $name);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasField(string $name) : bool
    {
        $name = $this->convertToPrimaryKeyName($name);
        return self::getMeta()->hasField($name);
    }

    /**
     * @return array
     */
    public function getDirtyAttributes() : array
    {
        return $this->attributes->getDirtyAttributes();
    }

    /**
     * @param string $name
     * @return ModelFieldInterface
     */
    public function getField(string $name) : ModelFieldInterface
    {
        $name = $this->convertToPrimaryKeyName($name);
        $field = self::getMeta()->getField($name);
        $field->setModel($this);
        return $field;
    }

    /**
     * @param string $name
     * @param $value
     * @throws Exception
     */
    public function setAttribute(string $name, $value)
    {
        $primaryKeyNames = self::getPrimaryKeyName(true);

        $meta = self::getMeta();
        $name = $meta->getMappingName($name);

        if ($meta->hasField($name)) {
            $field = $meta->getField($name);
            $attributeName = $field->getAttributeName();

            if (in_array($attributeName, $primaryKeyNames) && $this->getAttribute($attributeName) !== $value) {
                $this->setIsNewRecord(true);
            }

            $this->attributes->setAttribute($attributeName, $value);
        } else {
            throw new Exception(get_class($this) . ' has no attribute named "' . $name . '".');
        }
    }

    /**
     * @param bool $asArray
     * @return array|int|null|string
     */
    public function getPrimaryKeyValues()
    {
        $keys = $this->getPrimaryKeyName(true);
        $values = [];
        foreach ($keys as $name) {
            $values[$name] = $this->attributes->getAttribute($name);
        }
        return $values;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getOldAttribute(string $name)
    {
        return $this->attributes->getOldAttribute($name);
    }

    /**
     * @return array
     */
    public function getAttributes() : array
    {
        $attributes = [];
        foreach (self::getMeta()->getAttributes() as $name) {
            $attributes[$name] = $this->attributes->getAttribute($name);
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function getOldAttributes() : array
    {
        return $this->attributes->getOldAttributes();
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasAttribute($name) : bool
    {
        return in_array($name, self::getMeta()->getAttributes());
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * @param null $instance
     * @return Manager
     */
    public static function objectsManager($instance = null)
    {
        $className = get_called_class();
        return new Manager($instance ? $instance : new $className);
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        $manager = $method . 'Manager';
        if (method_exists($this, $manager)) {
            return call_user_func_array([$this, $manager], array_merge([$this], $args));

        } elseif (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);

        } else {
            throw new Exception('Call unknown method ' . $method);
        }
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($method, $args)
    {
        $manager = $method . 'Manager';
        $className = get_called_class();

        if (method_exists($className, $manager)) {
            return call_user_func_array([$className, $manager], $args);

        } elseif (method_exists($className, $method)) {
            return call_user_func_array([$className, $method], $args);

        } else {
            throw new Exception("Call unknown method {$method}");
        }
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $errors = [];
        $meta = self::getMeta();

        /* @var $field \Mindy\Orm\Fields\Field */
        foreach ($meta->getFields() as $name => $field) {
            if (
                $field instanceof AutoField ||
                $field instanceof ManyToManyField ||
                $field instanceof HasManyField
            ) {
                continue;
            }

            $value = $this->getAttribute($name);
            $field->setModel($this);
            $field->setValue($value);
            if ($field->isValid() === false) {
                $errors[$name] = $field->getErrors();
            }
        }

        $this->setErrors($errors);
        return count($errors) == 0;
    }

    /**
     * @param string $name
     * @return int|null|string
     */
    public function getAttribute(string $name)
    {
        return $this->attributes->getAttribute($name);
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

    abstract public function update(array $fields = []) : bool;

    abstract public function insert(array $fields = []) : bool;

    /**
     * @param array $fields
     * @return bool
     */
    public function save(array $fields = []) : bool
    {
        if ($this->getIsNewRecord()) {
            return $this->insert($fields);
        } else {
            return $this->update($fields);
        }
    }

    /**
     * @param array $row
     * @return ModelInterface
     */
    public static function create(array $row = [])
    {
        $className = get_called_class();
        return new $className($row);
    }

    /**
     * @return MetaData
     */
    public static function getMeta()
    {
        return MetaData::getInstance(get_called_class());
    }

    /**
     * @return bool
     */
    public function getIsNewRecord() : bool
    {
        return $this->isNewRecord;
    }

    /**
     * @param bool $value
     */
    public function setIsNewRecord(bool $value)
    {
        $this->isNewRecord = $value;
    }

    /**
     * @param bool $asArray
     * @return array|string
     */
    public static function getPrimaryKeyName($asArray = false)
    {
        return self::getMeta()->getPrimaryKeyName($asArray);
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function getFieldValue(string $name)
    {
        $field = $this->getField($name);
        $field->setValue($this->getAttribute($field->getAttributeName()));
        return $field->getValue();
    }

    /**
     * @return string
     */
    public static function tableName() : string
    {
        $classMap = explode('\\', get_called_class());
        $tableName = end($classMap);
        return trim(strtolower(preg_replace('/(?<![A-Z])[A-Z]/', '_\0', $tableName)), '_');
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return $this->hasField($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getFieldValue($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->setAttribute($offset, null);
    }
}