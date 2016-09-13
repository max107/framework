<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:50
 */

namespace Mindy\Form;

class ModelForm extends Form
{
    /**
     * @var FormModelInterface
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
     * @param FormModelInterface $model
     */
    private function initializeForm(FormModelInterface $model)
    {
        if ($this->initialized) {
            return;
        }
        foreach ($model->getFieldsInit() as $name => $modelField) {
            /** @var FieldInterface $field */
            $field = $modelField->getFormField();
            $field->setName($name);
            $field->setForm($this);
            $this->fields[$name] = $field;
        }
    }

    /**
     * @return bool
     */
    public function save() : bool
    {
        $model = $this->getModel();
        $model->setAttributes($this->getAttributes());
        $this->model = $model;
        return $model->save();
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setModelAttributes(array $attributes)
    {
        $model = $this->getModel();
        $model->setAttributes($attributes);
        $this->model = $model;
        return $this;
    }
}