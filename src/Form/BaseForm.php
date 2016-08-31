<?php

namespace Mindy\Form;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;
use Mindy\Helper\Collection;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Helper\Traits\RenderTrait;
use Mindy\Validation\Interfaces\IValidateObject;
use Mindy\Validation\Traits\ValidateObject;

/**
 * Class BaseForm
 * @package Mindy\Form
 */
abstract class BaseForm implements IteratorAggregate, Countable, ArrayAccess, IValidateObject
{
    use Accessors, Configurator, ValidateObject, RenderTrait;

    public $usePrefix = true;
    /**
     * @var string
     */
    public $template = 'core/form/block.html';
    /**
     * @var string
     */
    private $_prefix;
    /**
     * @var int
     */
    private $_id;
    /**
     * @var array
     */
    public static $ids = [];
    /**
     * @var bool
     */
    public $enableCreateButton = false;
    /**
     * @var \Mindy\Form\Fields\Field[]
     */
    protected $_fields = [];
    /**
     * @var array
     */
    protected $_renderFields = [];
    /**
     * @var array
     */
    private $_exclude = [];
    /**
     * @var bool
     */
    private $_renderErrors = true;

    public function __construct(array $config = [])
    {
        if (array_key_exists('exclude', $config)) {
            $this->_exclude = $config['exclude'];
            unset($config['exclude']);
        }
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
        $this->initFields();
        $this->setRenderFields(array_keys($this->getFieldsInit()));
    }

    /**
     * @param $prefix
     * @return array
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
    }

    /**
     * @return array
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function getName()
    {
        return $this->classNameShort();
    }

    public function getFieldsets()
    {
        return [];
    }

    public function __get($name)
    {
        if ($this->hasField($name)) {
            return $this->getField($name);
        } else {
            return $this->__getInternal($name);
        }
    }

    public function __clone()
    {
        $this->_id = null;

        foreach ($this->_fields as $name => $field) {
            $newField = clone $field;
            $newField->setForm($this);
            $this->_fields[$name] = $newField;
        }
    }

    public function __set($name, $value)
    {
        if ($this->hasField($name)) {
            $this->getField($name)->setValue($value);
        } else {
            $this->__setInternal($name, $value);
        }
    }

    public function getId()
    {
        if ($this->_id === null) {
            if (array_key_exists(self::class, self::$ids)) {
                self::$ids[self::class]++;
            } else {
                self::$ids[self::class] = 0;
            }

            $this->_id = self::$ids[self::class];
        }

        return $this->_id;
    }

    /**
     * Initialize fields
     * @void
     */
    public function initFields()
    {
        $prefix = $this->getPrefix();
        $fields = $this->getFields();
        foreach ($fields as $name => $config) {
            if (in_array($name, $this->_exclude)) {
                continue;
            }

            if (!is_array($config)) {
                $config = ['class' => $config];
            }

            $this->_fields[$name] = Creator::createObject(array_merge([
                'name' => $name,
                'form' => $this,
                'prefix' => $prefix,
                'enableCreateButton' => $this->enableCreateButton
            ], $config));
        }
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [];
    }

    /**
     * Please avoid this method for render form
     * @return string
     */
    public function __toString()
    {
        try {
            return (string)$this->render();
        } catch (Exception $e) {
            return (string)$e;
        }
    }

    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRenderErrors($value)
    {
        $this->_renderErrors = $value;
        return $this;
    }

    /**
     * @param null $template
     * @return string
     */
    public function render($template = null)
    {
        if (empty($template)) {
            $template = $this->template;
        }
        return $this->renderTemplate($template, [
            'form' => $this,
            'errors' => $this->_renderErrors ? $this->getErrors() : []
        ]);
    }

    /**
     * Set fields for render
     * @param array $fields
     * @throws \Exception
     * @return $this
     */
    public function setRenderFields(array $fields = [])
    {
        if (empty($fields)) {
            $fields = array_keys($this->getFieldsInit());
        }
        $this->_renderFields = [];
        foreach ($fields as $name) {
            if ($this->hasField($name)) {
                $this->_renderFields[] = $name;
            } else {
                throw new Exception("Field $name not found");
            }
        }
        return $this;
    }

    /**
     * @return array|mixed
     */
    protected function getExclude()
    {
        return $this->_exclude;
    }

    /**
     * @return array
     */
    public function getRenderFields()
    {
        $fields = [];
        foreach ($this->getFieldsInit() as $name => $field) {
            if (in_array($name, $this->_renderFields)) {
                $fields[$name] = $field;
            }
        }
        return $fields;
    }

    /**
     * Return initialized fields
     * @return \Mindy\Form\Fields\Field[]
     */
    public function getFieldsInit()
    {
        return $this->_fields;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function hasField($attribute)
    {
        return array_key_exists($attribute, $this->_fields);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $this->isValidInternal();
        return $this->hasErrors() === false;
    }

    /**
     * @param $attribute
     * @return \Mindy\Form\Fields\Field
     */
    public function getField($attribute)
    {
        return $this->_fields[$attribute];
    }

    public function prepare(array $data, array $files = [], $fixFiles = true)
    {
        return $this->merge($fixFiles ? $this->reformatFilesArray($files) : $files, $data, true);
    }

    public function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key) && !$preserveNumericKeys) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * Fix broken $_FILES array
     * @param $data
     * @return array
     */
    public function reformatFilesArray($data)
    {
        $n = [];
        foreach ($data as $baseName => $params) {
            foreach ($params as $innerKey => $value) {
                foreach ($value as $inlineName => $item) {
                    if (is_array($item)) {
                        foreach($item as $index => $t) {
                            $key = key($t);
                            $n[$baseName][$inlineName][$index][$key][$innerKey] = $t[$key];
                        }
                    } else {
                        $n[$baseName][$inlineName][$innerKey] = $item;
                    }
                }
            }
        }
        return $n;
    }

    /**
     * @param array|Collection $data
     * @param array|Collection $files
     * @return $this
     * @throws Exception
     */
    public function populate($data, $files = [])
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        } else if (!is_array($data)) {
            throw new Exception('$data must be a array');
        }

        $fixFiles = true;
        if ($files instanceof Collection) {
            $fixFiles = false;
            $files = $files->all();
        }

        $tmp = empty($files) ? $data : $this->prepare($data, $files, $fixFiles);

        if ($this->usePrefix) {
            if (!isset($tmp[$this->classNameShort()])) {
                return $this;
            }

            $data = $tmp[$this->classNameShort()];
            $this->setAttributes($data);
        } else {
            $this->setAttributes($tmp);
        }
        return $this;
    }

    /**
     * @param \Mindy\Form\BaseForm|\Mindy\Form\ModelForm $owner
     * @param array $attributes
     * @return array
     */
    public function beforeSetAttributes($owner, array $attributes)
    {
        return $attributes;
    }

    public function afterOwnerSave($owner)
    {
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setAttributes(array $data)
    {
        $fields = $this->getFieldsInit();
        if (empty($data)) {
            foreach ($fields as $field) {
                $field->setValue(null);
            }
        } else {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $fields)) {
                    $fields[$key]->setValue($value);
                }
            }
        }
        return $this;
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        $this->clearErrorsInternal($attribute);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getFieldsInit());
    }

    public function count()
    {
        return count($this->getFieldsInit());
    }

    public function offsetSet($offset, $value)
    {
        if ($this->hasField($offset)) {
            $this->getField($offset)->setValue($value);
        } else {
            throw new Exception('Field isnt exists');
        }
    }

    public function offsetExists($offset)
    {
        return $this->hasField($offset);
    }

    public function offsetUnset($offset)
    {
        throw new Exception('Method not supported');
    }

    /**
     * @param mixed $offset
     * @return Fields\Field
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        if ($this->hasField($offset)) {
            return $this->getField($offset);
        }

        throw new Exception('Field isnt exists');
    }

    /**
     * Return form attributes
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [];
        foreach ($this->getFieldsInit() as $name => $field) {
            $attributes[$name] = $field->getValue();
        }
        return $attributes;
    }

    public function getJsonErrors()
    {
        $data = [];
        foreach ($this->getErrors() as $name => $errors) {
            $data[$this->getField($name)->getHtmlName()] = $errors;
        }
        return $data;
    }
}
