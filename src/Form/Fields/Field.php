<?php

namespace Mindy\Form\Fields;

use Closure;
use Exception;
use Mindy\Form\BaseForm;
use Mindy\Form\ModelForm;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Validation\Interfaces\IValidateField;
use Mindy\Validation\RequiredValidator;
use Mindy\Validation\Traits\ValidateField;

/**
 * Class Field
 * @package Mindy\Form
 */
abstract class Field implements IValidateField
{
    use Accessors, Configurator, ValidateField;

    /**
     * @var bool Технические аттрибуты для inline моделей
     */
    public $hidden = false;
    /**
     * @var bool Технические аттрибуты для inline моделей
     */
    public $delete = false;
    /**
     * @var mixed
     */
    public $value;
    /**
     * @var bool
     */
    public $required = false;
    /**
     * @var bool
     */
    public $escape = true;
    /**
     * @var string
     */
    public $inputType;
    /**
     * @var string
     */
    public $template = "<input type='{type}' id='{id}' value='{value}' name='{name}'{html}/>";
    /**
     * @var string
     */
    public $hint;
    /**
     * @var string
     */
    public $type = 'text';
    /**
     * @var string
     */
    public $html = '';
    /**
     * @var array
     */
    public $choices = [];
    /**
     * @var array
     */
    public $widget = [];
    /**
     * @var string html class for render hint
     */
    public $hintClass = 'form-hint-text';
    /**
     * @var
     */
    public $label;
    /**
     * @var string
     */
    public $errorClass = 'error';
    /**
     * @var bool
     */
    public $enableCreateButton = false;
    /**
     * @var
     */
    private $_name;
    /**
     * @var BaseForm
     */
    private $_form;
    /**
     * @var string
     */
    private $_validatorClass = '\Mindy\Form\Validator\Validator';
    /**
     * @var string
     */
    private $_prefix;
    /**
     * Variable for avoid recursion
     * @var bool
     */
    private $_renderWidget = true;

    public function init()
    {
        if ($this->required) {
            $this->validators[] = new RequiredValidator();
        }
        foreach ($this->validators as $validator) {
            /** @var $validator \Mindy\Validation\Validator */
            if (is_subclass_of($validator, $this->_validatorClass)) {
                $validator->setName($this->label ? $this->label : $this->name);
            }
        }
    }

    public function __toString()
    {
        try {
            return (string)$this->render();
        } catch (Exception $e) {
            echo (string)$e;
            die();
        }
    }

    /**
     * @param BaseForm $form
     * @return $this
     */
    public function setForm(BaseForm $form)
    {
        $this->_form = $form;
        return $this;
    }

    /**
     * @return BaseForm
     */
    public function getForm()
    {
        return $this->_form;
    }

    public function setPrefix($value)
    {
        $this->_prefix = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        $form = $this->getForm();
        if ($form === null) {
            return '';
        }
        $prefix = $this->_prefix ? $this->_prefix : $form->getPrefix();
        if ($prefix) {
            return $prefix . '[' . $form->classNameShort() . '][' . $this->getId() . ']';
        } else {
            return $form->classNameShort();
        }
    }

    public function getId()
    {
        return $this->form->getId();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    private function setRenderWidget($value)
    {
        $this->_renderWidget = $value;
        return $this;
    }

    public function renderInput()
    {
        if (empty($this->widget) === false && $this->_renderWidget) {
            if (is_string($this->widget)) {
                $widget = Creator::createObject(['class' => $this->widget]);
            } else if (is_array($this->widget)) {
                $widget = Creator::createObject($this->widget);
            } else {
                $widget = $this->widget;
            }
            $this->setRenderWidget(false);
            $input = $widget->setField($this)->render();
            $this->setRenderWidget(true);
            return $input;
        } else {
            $value = $this->getValue();
            $attributes = $this->getHtmlAttributes();
            $input = strtr($this->template, [
                '{type}' => $this->type,
                '{id}' => $this->getHtmlId(),
                '{name}' => $this->getHtmlName(),
                '{value}' => $this->escape ? htmlspecialchars($value, ENT_QUOTES) : $value,
                '{html}' => empty($attributes) ? '' : ' ' . $attributes
            ]);

            return $input;
        }
    }

    public function render()
    {
        $label = $this->renderLabel();
        $input = $this->renderInput();

        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();
        return implode("\n", [$label, $input, $hint, $errors]);
    }

    public function getHtmlName()
    {
        $form = $this->getForm();
        if ($form === null) {
            return '';
        }

        if ($form->usePrefix) {
            return $this->getPrefix() . '[' . $this->name . ']';
        } else {
            return $this->name;
        }
    }

    public function getHtmlAttributes()
    {
        if (is_array($this->html)) {
            $html = [];
            foreach ($this->html as $name => $value) {
                if ($name === 'id') {
                    continue;
                }

                if ($value === true) {
                    $value = 'true';
                } else if ($value === false) {
                    $value = 'false';
                }

                if (is_numeric($name)) {
                    $html[] = $value;
                } else {
                    $html[] = $name . "='" . $value . "'";
                }
            }
            return trim(implode(' ', $html));
        } else {
            return trim($this->html);
        }
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
//        if ($this->value === null) {
//            if ($this->form instanceof ModelForm) {
//                $instance = $this->form->getInstance();
//                if ($instance->hasField($this->name)) {
//                    return $instance->getField($this->name)->getValue();
//                }
//            }
//        }
        return $this->value;
    }

    public function renderLabel()
    {
        if ($this->label === false) {
            return '';
        }

        if ($this->label) {
            $label = $this->label;
        } else {
            if ($this->form instanceof ModelForm) {
                $instance = $this->form->getModel();
                if ($instance->hasField($this->name)) {
                    $verboseName = $instance->getField($this->name)->verboseName;
                    if ($verboseName) {
                        $label = $verboseName;
                    }
                }
            }

            if (!isset($label)) {
                $label = ucfirst($this->name);
            }
        }

        return strtr("<label for='{for}'>{label}{star}</label>", [
            '{for}' => $this->getHtmlId(),
            '{label}' => $label,
            '{star}' => $this->required ? " <span class='required'>*</span>" : ''
        ]);
    }

    public function renderErrors()
    {
        $errors = "";
        foreach ($this->getErrors() as $error) {
            $errors .= "<li>{$error}</li>";
        }

        return strtr('<ul class="{errorClass}" id="{id}_errors"{html}>{errors}</ul>', [
            '{errorClass}' => $this->errorClass,
            '{id}' => $this->getHtmlId(),
            '{html}' => empty($errors) ? " style='display:none;'" : '',
            '{errors}' => $errors
        ]);
    }

    public function renderHint()
    {
        return strtr('<p class="{class}">{hint}</p>', [
            '{class}' => $this->hintClass,
            '{hint}' => $this->hint
        ]);
    }

    /**
     * Format:
     * [
     *     "Main" => [
     *         "Name", "Url", "Content"
     *     ],
     *     "Extra" => [ ... ]
     * ]
     * @return array
     */
    public function getFieldSets()
    {
        return [];
    }

    public function getHtmlId()
    {
        if (isset($this->html['id'])) {
            return $this->html['id'];
        } else {
            return $this->getHtmlPrefix() . $this->getName();
        }
    }

    public function getHtmlPrefix()
    {
        $prefix = $this->getPrefix();
        if (empty($prefix)) {
            return '';
        }

        return rtrim(str_replace(['][', '[]', '[', ']'], '_', $prefix), '_') . '_';
    }

    public function getChoices()
    {
        return $this->choices instanceof Closure ? $this->choices->__invoke() : $this->choices;
    }
}
