<?php

namespace Mindy\Orm\Fields;

use Closure;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Orm\Base;
use Mindy\Orm\Model;
use Mindy\Query\Schema\Schema;
use Symfony\Component\Validator\Constraints as Assert;
use Mindy\Validation\UniqueValidator;
use Mindy\Validation\ValidationAwareTrait;

/**
 * Class Field
 * @package Mindy\Orm
 */
abstract class Field
{
    use Accessors;
    use Configurator;
    use ValidationAwareTrait;

    public $verboseName = '';

    public $null = false;

    public $default = null;

    public $length = 0;

    public $required;

    public $value;

    public $editable = true;

    public $choices = [];

    public $helpText;

    public $unique = false;

    public $primary = false;

    public $autoFetch = false;

    protected $name;

    protected $ownerClassName;

    private $_validatorClass = '\Mindy\Validation\Validator';

    private $_extraFields = [];
    /**
     * @var \Mindy\Orm\Model
     */
    private $_model;

    /**
     * @var array
     */
    protected $validators = [];

    /**
     * @return array
     */
    public function getValidationConstraints() : array
    {
        $constraints = [];
        if ($this->required) {
            $constraints[] = new Assert\NotBlank();
        }
        if ($this->unique) {
            // TODO
            $constraints[] = new Assert\Callback(function () {
                return $this->getModel()->objects()->filter([$this->name => $this->getValue()])->count() > 0;
            });
        }
        if (!empty($this->choices)) {
            $constraints[] = new Assert\Choice([
                'choices' => $this->choices instanceof Closure ? $this->choices->__invoke() : $this->choices
            ]);
        }
        return array_merge($constraints, $this->validators);
    }

    public function sqlDefault()
    {
        if (is_numeric($this->default) === false && empty($this->default)) {
            return '';
        }
        return 'DEFAULT ' . $this->default;
    }

    public function sqlNullable()
    {
        return $this->null ? 'NULL' : 'NOT NULL';
    }

    abstract public function sqlType();

    public function getSql(Schema $schema)
    {
        $sql = $this->getSqlType($schema);
        if ($sql === false) {
            return false;
        }

        if (($nullable = $this->sqlNullable()) && empty($nullable) === false) {
            $sql .= ' ' . $nullable;
        }

        if (($default = $this->sqlDefault()) && empty($default) === false) {
            $sql .= ' ' . $default;
        }

        if (($extra = $this->sqlExtra()) && empty($extra) === false) {
            $sql .= ' ' . $extra;
        }

        return trim($sql);
    }

    public function sqlExtra()
    {
        return '';
    }

    /**
     * @param Schema $schema
     * @return string
     */
    protected function getSqlType(Schema $schema)
    {
        return $schema->getColumnType($this->sqlType());
    }

    /**
     * @return Field[]
     */
    public function getExtraFieldsInit()
    {
        return $this->_extraFields;
    }

    public function getExtraField($name)
    {
        return $this->_extraFields[$name];
    }

    public function hasExtraField($name)
    {
        return array_key_exists($name, $this->_extraFields);
    }

    public function setExtraField($name, Field $field)
    {
        $this->_extraFields[$name] = $field;
        return $this;
    }

    public function canBeEmpty()
    {
        return !$this->required && $this->null || !is_null($this->default) || $this->autoFetch === true;
    }

    public function setModel(Base $model)
    {
        $this->_model = $model;
        return $this;
    }

    public function setModelClass($className)
    {
        $this->ownerClassName = $className;
        return $this;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->_model;
    }

    public function getValue()
    {
        if (empty($this->value)) {
            return $this->null == true ? null : $this->default;
        }
        return  $this->value;
    }

    public function getDbPrepValue()
    {
        return $this->getValue();
    }

    public function cleanValue()
    {
        $this->value = null;
    }

    public function setDbValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function setValue($value)
    {
        return $this->value = $value;
    }

    public function getOptions()
    {
        return [
            'sqlType' => $this->sqlType(),
            'null' => $this->null,
            'default' => $this->default,
            'length' => $this->length,
            'required' => $this->required,
            'primary' => $this->primary
        ];
    }

    public function hash()
    {
        return md5(serialize($this->getOptions()));
    }

    public function getFormValue()
    {
        return $this->getValue();
    }

    public function isRequired()
    {
        return $this->required === true;
    }

    public function setName($name)
    {
        $this->name = $name;
        foreach ($this->validators as $validator) {
            if (is_subclass_of($validator, $this->_validatorClass)) {
                $validator->setName($name);
                $validator->setModel($this->getModel());
            }
        }
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVerboseName(Model $model)
    {
        if ($this->verboseName) {
            return $this->verboseName;
        } else {
            $name = str_replace('_', ' ', ucfirst($this->name));
            if (method_exists($model, 'getModule')) {
                return $model->getModule()->t($name);
            } else {
                return $name;
            }
        }
    }

    public function getExtraFields()
    {
        return [];
    }

    public function onAfterInsert()
    {

    }

    public function onAfterUpdate()
    {

    }

    public function onAfterDelete()
    {

    }

    public function onBeforeInsert()
    {

    }

    public function onBeforeUpdate()
    {

    }

    public function onBeforeDelete()
    {

    }

    public function getFormField($form, $fieldClass = null, array $extra = [])
    {
        if ($this->primary || $this->editable === false) {
            return null;
        }

        if ($fieldClass === null) {
            $fieldClass = $this->choices ? \Mindy\Form\Fields\DropDownField::className() : \Mindy\Form\Fields\CharField::className();
        } elseif ($fieldClass === false) {
            return null;
        }

        $validators = [];
        if ($form->hasField($this->name)) {
            $field = $form->getField($this->name);
            $validators = $field->validators;
        }

        if (($this->null === false || $this->required) && $this->autoFetch === false && ($this instanceof BooleanField) === false) {
            $validator = new RequiredValidator;
            $validator->setName($this->name);
            $validator->setModel($this);
            $validators[] = $validator;
        }

        if ($this->unique) {
            $validator = new UniqueValidator;
            $validator->setName($this->name);
            $validator->setModel($this);
            $validators[] = $validator;
        }

        return Creator::createObject(array_merge([
            'class' => $fieldClass,
            'required' => !$this->canBeEmpty(),
            'form' => $form,
            'choices' => $this->choices,
            'name' => $this->name,
            'label' => $this->verboseName,
            'hint' => $this->helpText,
            'validators' => array_merge($validators, $this->validators),
            'value' => $this->default ? $this->default : null

//            'html' => [
//                'multiple' => $this->value instanceof RelatedManager
//            ]
        ], $extra));
    }

    public function toArray()
    {
        return $this->getValue();
    }

    public function toText()
    {
        $value = $this->getValue();
        if (isset($this->choices[$value])) {
            $value = $this->choices[$value];
        }
        return $value;
    }

    public function hasChoices()
    {
        return !empty($this->choices);
    }
}
