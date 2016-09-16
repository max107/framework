<?php

namespace Mindy\Orm\Fields;

use Closure;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Orm\Base;
use Mindy\Orm\Model;
use Mindy\Orm\ModelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Mindy\Validation\UniqueValidator;
use Mindy\Validation\ValidationAwareTrait;

/**
 * Class Field
 * @package Mindy\Orm
 */
abstract class Field implements ModelFieldInterface
{
    use Accessors;
    use Configurator;
    use ValidationAwareTrait;

    /**
     * @var string|null|false
     */
    public $comment;
    /**
     * @var bool
     */
    public $null = false;
    /**
     * @var null|string|int
     */
    public $default = null;
    /**
     * @var int|string
     */
    public $length = 0;

    public $verboseName = '';

    public $required;

    public $editable = true;

    public $choices = [];

    public $helpText;

    public $unique = false;

    public $primary = false;

    public $autoFetch = false;

    protected $name;

    protected $ownerClassName;

    /**
     * @var \Mindy\Orm\Model
     */
    private $_model;

    /**
     * @var array
     */
    protected $validators = [];
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var mixed
     */
    protected $dbValue;

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

    /**
     * @return Column
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getColumn()
    {
        return new Column(
            $this->name,
            Type::getType($this->getSqlType()),
            $this->getSqlOptions()
        );
    }

    /**
     * @return array
     */
    public function getSqlIndexes() : array
    {
        $indexes = [];
        if ($this->primary) {
            $indexes[] = new Index('PRIMARY', [$this->name], true, true);
        } else if ($this->unique && !$this->primary) {
            $indexes[] = new Index($this->name . '_idx', [$this->name], true, false);
        }
        return $indexes;
    }

    /**
     * @return array
     */
    public function getSqlOptions() : array
    {
        $options = [];

        foreach (['length', 'default', 'comment'] as $key) {
            $options[$key] = $this->{$key};
        }

        if ($this->null) {
            $options['notnull'] = false;
            unset($options['default']);
        }

        return $options;
    }

    /**
     * @return string|bool
     */
    public function getAttributeName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    abstract public function getSqlType();

    public function canBeEmpty()
    {
        return !$this->required && $this->null || !is_null($this->default) || $this->autoFetch === true;
    }

    /**
     * @param ModelInterface $model
     * @return $this
     */
    public function setModel(ModelInterface $model)
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

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->setDbValue($value);
    }

    /**
     * @return int|mixed|null|string
     */
    public function getValue()
    {
        if (empty($this->value)) {
            return $this->null === true ? null : $this->default;
        }
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDbValue($value)
    {
        $this->dbValue = $value;
        return $this;
    }

    /**
     * @return int|mixed|null|string
     */
    public function getDbValue()
    {
        if (empty($this->dbValue)) {
            return $this->null === true ? null : $this->default;
        }
        return $this->dbValue;
    }

    public function cleanValue()
    {
        $this->value = null;
    }

    public function getFormValue()
    {
        return $this->getValue();
    }

    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
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
            $fieldClass = $this->choices ? \Mindy\Form\Fields\SelectField::class : \Mindy\Form\Fields\TextField::class;
        } elseif ($fieldClass === false) {
            return null;
        }

        return Creator::createObject(array_merge([
            'class' => $fieldClass,
            'required' => !$this->canBeEmpty(),
            'form' => $form,
            'choices' => $this->choices,
            'name' => $this->name,
            'label' => $this->verboseName,
            'hint' => $this->helpText,
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
