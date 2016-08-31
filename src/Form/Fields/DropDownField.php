<?php

namespace Mindy\Form\Fields;

use Closure;
use Mindy\Base\Mindy;
use Mindy\Form\Form;
use Mindy\Form\ModelForm;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Manager;
use Mindy\Orm\Model;

/**
 * Class DropDownField
 * @package Mindy\Form
 */
class DropDownField extends Field
{
    /**
     * @var array
     */
    public $choices = [];
    /**
     * Span tag needed because: http://stackoverflow.com/questions/23920990/firefox-30-is-not-hiding-select-box-arrows-anymore
     * @var string
     */
    public $template = "<span class='select-holder'><select id='{id}' name='{name}' {html}>{input}</select></span>{create}";
    /**
     * @var bool
     */
    public $multiple = false;
    /**
     * @var string
     */
    public $empty = '';
    /**
     * @var array
     */
    public $disabled = [];
    /**
     * @var
     */
    public $createUrl;

    public function render()
    {
        $label = $this->renderLabel();
        $input = $this->renderInput();

        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();

        return implode("\n", [$label, $input, $hint, $errors]);
    }

    public function renderInput()
    {
        $name = $this->getHtmlName();
        return implode("\n", ["<input type='hidden' value='' name='{$name}' />", strtr($this->template, [
            '{type}' => $this->type,
            '{id}' => $this->getHtmlId(),
            '{input}' => $this->getInputHtml(),
            '{name}' => $this->multiple ? $this->getHtmlName() . '[]' : $this->getHtmlName(),
            '{html}' => $this->getHtmlAttributes(),
            '{create}' => $this->renderCreate()
        ])]);
    }

    public function renderCreate()
    {
        if ($this->enableCreateButton && $this->getForm() instanceof ModelForm) {
            $model = $this->getForm()->getModel();
            $field = $model->getField($this->getName());
            if ($field instanceof ForeignField) {
                $modelClass = basename(str_replace('\\', '/', $field->modelClass));
                $tmp = explode('\\', $field->modelClass);
                $moduleId = $tmp[1];
                $className = strtr('\Modules\{id}\Admin\{admin}', [
                    '{id}' => $moduleId,
                    '{admin}' => $modelClass . 'Admin'
                ]);

                if (class_exists($className)) {
                    if (empty($this->createUrl)) {
                        $this->createUrl = Mindy::app()->urlManager->reverse('admin:action', [
                            'module' => $moduleId,
                            'admin' => $modelClass . 'Admin',
                            'action' => 'create'
                        ]);
                    }
                }


                if (empty($this->createUrl)) {
                    return '';
                }

                return "<a href='#' class='button icon create-popup-form' data-id='{$this->getHtmlId()}' data-url='{$this->createUrl}'><i class=\"icon plus\"></i></a>";
            }
        }

        return '';
    }

    protected function getInputHtml()
    {
        $out = '';
        $data = [];
        $selected = [];

        if (!empty($this->choices)) {
            $choices = $this->choices;
        } else if ($this->form instanceof ModelForm) {
            $choices = $this->form->getModel()->getField($this->name)->choices;
        }

        if (!empty($choices)) {
            if ($choices instanceof Closure) {
                $data = $choices->__invoke();
            } else {
                $data = $choices;
            }

            if (!$this->required) {
                $data = ['' => $this->empty] + $data;

            }

            $value = $this->getValue();
            if ($value) {
                if ($value instanceof Manager) {
                    $selected = $value->valuesList(['pk'], true);
                } else if ($value instanceof Model) {
                    $selected[] = $value->pk;
                } else if (is_array($value)) {
                    $selected = $value;
                } else {
                    $selected[] = $value;
                }
            }

            if ($this->multiple) {
                $this->html['multiple'] = 'multiple';
            }
            return $this->valueToHtml($data, $selected);
        }

        if ($this->form instanceof ModelForm && $this->form->getModel()->hasField($this->name)) {
            $model = $this->form->getModel();
            $field = $model->getField($this->name);

            if ($field instanceof ManyToManyField) {
                $this->multiple = true;

                $modelClass = $field->modelClass;
                $models = $modelClass::objects()->all();

                if ($value = $this->getValue()) {
                    if ($value instanceof Manager) {
                        $selectedTmp = $value->all();
                        foreach ($selectedTmp as $item) {
                            $selected[] = $item->pk;
                        }
                    } else {
                        $selected = is_array($value) ? $value : [$value];
                    }
                }

                $this->html['multiple'] = 'multiple';

                foreach ($models as $item) {
                    $data[$item->pk] = (string)$item;
                }
            } elseif ($field instanceof HasManyField) {
                $this->multiple = true;

                $modelClass = $field->modelClass;
                $models = $modelClass::objects()->all();

                $this->html['multiple'] = 'multiple';

                foreach ($models as $item) {
                    $data[$item->pk] = (string)$item;
                }
            } elseif ($field instanceof ForeignField) {
                $modelClass = $field->modelClass;
                $qs = $modelClass::objects();
                if (get_class($model) == $modelClass && $model->getIsNewRecord() === false) {
                    $qs = $qs->exclude(['pk' => $model->pk]);
                }
                /* @var $modelClass \Mindy\Orm\Model */
                if (!$this->required) {
                    $data[''] = $this->empty;
                }
                if ($value = $this->getValue()) {
                    $selected[] = $value instanceof Model ? $value->pk : $value;
                }
                foreach ($qs->all() as $item) {
                    $data[$item->pk] = (string)$item;
                }
            } else {
                $data = parent::getValue();
            }
        } else {
            $data = parent::getValue();
        }

        if (is_array($data)) {
            return $this->valueToHtml($data, $selected);
        } else {
            return $out;
        }
    }

    protected function valueToHtml(array $data, array $selected = [])
    {
        $out = '';
        foreach ($data as $value => $name) {
            $out .= strtr("<option value='{value}'{selected}{disabled}>{name}</option>", [
                '{value}' => $value,
                '{name}' => $name,
                '{disabled}' => in_array($value, $this->disabled) ? " disabled" : "",
                '{selected}' => in_array($value, $selected) ? " selected='selected'" : ""
            ]);
        };
        return $out;
    }
}
