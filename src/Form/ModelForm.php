<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:50
 */

namespace Mindy\Form;

use Mindy\Creator\Creator;

/**
 * Class ModelForm
 * @package Mindy\Form
 */
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
     * @param FormModelInterface|\Mindy\Orm\ModelInterface $model
     */
    private function initializeForm(FormModelInterface $model)
    {
        if ($this->initialized === false) {
            $fields = $this->getFields();

            foreach ($model->getAttributes() as $name => $value) {
                /** @var FieldInterface $field */
                $modelField = $model->getField($name);
                $field = $modelField->getFormField();

                if ($field === null || $field === false) {
                    continue;
                }

                if (($field instanceof FieldInterface) === false) {
                    $field = Creator::createObject($field);
                } else {
                    $field->configure([
                        'name' => $name
                    ]);
                }

                if (isset($fields[$name]) && is_array($fields[$name])) {
                    $field->configure($fields[$name]);
                }

                $this->fields[$name] = $field;
            }

            $this->initialized = true;
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