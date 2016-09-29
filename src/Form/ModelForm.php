<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:50
 */

namespace Mindy\Form;

use Mindy\Creator\Creator;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;

/**
 * Class ModelForm
 * @package Mindy\Form
 */
class ModelForm extends Form
{
    /**
     * @var \Mindy\Orm\ModelInterface|FormModelInterface
     */
    protected $model;
    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param FormModelInterface $model
     */
    public function setModel(FormModelInterface $model)
    {
        $this->model = $model;
        $this->initializeForm($model);
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param FormModelInterface|\Mindy\Orm\ModelInterface $model
     */
    private function initializeForm(FormModelInterface $model)
    {
        if ($this->initialized === false) {
            $fields = $this->getFields();
            $attributes = $model->getAttributes();
            foreach ($model->getMeta()->getFields() as $name => $field) {
                $modelField = $model->getField($name);
                $field = $modelField->getFormField();

                if ($field === null || $field === false) {
                    continue;
                }

                if (($field instanceof FieldInterface) === false) {
                    $field = Creator::createObject(isset($fields[$name]) ? array_merge($field, $fields[$name]) : $field);
                } else {
                    $field->configure(['name' => $name]);
                }

                if (isset($fields[$name]) && is_array($fields[$name])) {
                    $field->configure($fields[$name]);
                }

                if (array_key_exists($name, $attributes)) {
                    $field->setValue($attributes[$name]);
                } else if ($modelField instanceof HasManyField || $modelField instanceof ManyToManyField) {
                    $field->setValue($modelField->getValue()->valuesList(['pk'], true));
                }
                $this->fields[$name] = $field;
            }

            $this->initialized = true;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasField($name) : bool
    {
        // Foreign fields with auto generated form
        if (array_key_exists($name . '_id', $this->fields)) {
            return true;
        }
        return parent::hasField($name);
    }

    /**
     * @param string $name
     * @return FieldInterface
     */
    public function getField(string $name) : FieldInterface
    {
        // Foreign fields with auto generated form
        if (array_key_exists($name . '_id', $this->fields)) {
            $name .= '_id';
        }
        return parent::getField($name);
    }

    /**
     * @return bool
     */
    public function save() : bool
    {
        $this->setModelAttributes($this->getAttributes());
        return $this->model->save();
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setModelAttributes(array $attributes)
    {
        $this->model->setAttributes($attributes);
        return $this;
    }
}