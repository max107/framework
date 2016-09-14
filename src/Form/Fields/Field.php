<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:54
 */

namespace Mindy\Form\Fields;

use Mindy\Form\FieldInterface;
use Mindy\Form\FormInterface;
use Mindy\Form\WidgetInterface;
use Mindy\Helper\Creator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

abstract class Field implements FieldInterface
{
    /**
     * @var string html class for render hint
     */
    public $hintClass = 'form-input-text';
    /**
     * @var string
     */
    public $errorClass = 'form-input-errors';
    /**
     * @var string
     */
    public $containerTemplate = '{label}{input}{hint}{errors}';
    /**
     * @var string
     */
    public $template = '';
    /**
     * @var string
     */
    protected $name;
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var FormInterface
     */
    protected $form;
    /**
     * @var bool
     */
    protected $required = true;
    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors = [];
    /**
     * @var array
     */
    protected $validators = [];
    /**
     * @var string|object|array|null
     */
    protected $widget;
    /**
     * @var array
     */
    protected $html = [];
    /**
     * @var string
     */
    protected $label = '';
    /**
     * @var string
     */
    protected $hint;
    /**
     * @var bool
     */
    protected $escape = true;
    /**
     * @var array
     */
    protected $choices = [];
    /**
     * Variable for avoid recursion
     * @var bool
     */
    private $_renderWidget = true;

    /**
     * NewField constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected function getValidator()
    {
        return Validation::createValidatorBuilder()->getValidator();
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->escape ? htmlspecialchars($this->value, ENT_QUOTES) : $this->value;
    }

    /**
     * @return array
     */
    protected function getValidationConstraints() : array
    {
        $constraints = [];
        if ($this->required) {
            $constraints[] = new Assert\NotBlank();
        }
        if (!empty($this->choices)) {
            $constraints[] = new Assert\Choice(['choices' => $this->choices]);
        }
        return $constraints;
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $constraints = array_merge($this->getValidationConstraints(), $this->validators);
        $errors = $this->getValidator()->validate($this->getValue(), $constraints);
        $this->setErrors($errors);
        return count($errors) === 0;
    }

    /**
     * @param ConstraintViolationListInterface $errors
     * @return $this
     */
    protected function setErrors(ConstraintViolationListInterface $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        $errors = [];
        foreach ($this->errors as $key => $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }

    /**
     * @return mixed|string
     */
    public function getHtmlId() : string
    {
        if (isset($this->html['id'])) {
            return $this->html['id'];
        } else {
            $form = $this->getForm();
            return implode('_', [$form->classNameShort(), $form->getId(), $this->name]);
        }
    }

    /**
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @return FormInterface
     */
    public function getForm() : FormInterface
    {
        if ($this->form === null) {
            throw new \LogicException('Missing form');
        }
        return $this->form;
    }

    /**
     * @return string
     */
    public function getHtmlAttributes() : string
    {
        $html = '';
        foreach ($this->html as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $html .= $key . '="' . $value . '" ';
        }
        return trim($html);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->render();
    }

    /**
     * @return string
     */
    public function renderLabel() : string
    {
        if ($this->label === false) {
            return '';
        }

        $label = $this->label;
        if ($this->required) {
            $label .= " <span class='required'>*</span>";
        }

        return strtr("<label for='{for}'>{label}</label>", [
            '{for}' => $this->getHtmlId(),
            '{label}' => $label,
        ]);
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

    /**
     * @return WidgetInterface
     */
    protected function createWidget() : WidgetInterface
    {
        if ($this->widget instanceof WidgetInterface) {
            return $this->widget;
        }

        if (is_string($this->widget)) {
            $widget = ['class' => $this->widget];
        } else {
            $widget = $this->widget;
        }

        return Creator::createObject($widget);
    }

    /**
     * @return string
     */
    public function getHtmlName() : string
    {
        $form = $this->getForm();
        if ($form === null) {
            return $this->name;
        }
        return $this->getForm()->classNameShort() . '[' . $this->name . ']';
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

    /**
     * @return string
     */
    public function renderInput() : string
    {
        if (empty($this->widget) === false && $this->_renderWidget) {
            $this->setRenderWidget(false);
            $input = $this->createWidget()->render($this);
            $this->setRenderWidget(true);
            return $input;
        } else {
            $input = strtr($this->template, [
                '{id}' => $this->getHtmlId(),
                '{name}' => $this->getHtmlName(),
                '{value}' => $this->getValue(),
                '{html}' => $this->getHtmlAttributes(),
            ]);

            return $input;
        }
    }

    /**
     * @return string
     */
    public function renderErrors() : string
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

    /**
     * @return string
     */
    public function renderHint() : string
    {
        return strtr('<p class="{class}">{hint}</p>', [
            '{class}' => $this->hintClass,
            '{hint}' => $this->hint
        ]);
    }

    /**
     * @return string
     */
    public function render() : string
    {
        return strtr($this->containerTemplate, [
            '{label}' => $this->renderLabel(),
            '{input}' => $this->renderInput(),
            '{errors}' => $this->renderErrors(),
            '{hint}' => $this->renderHint()
        ]);
    }

    /**
     * @return bool
     */
    public function isRequired() : bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getLabel() : string
    {
        if (empty($this->label)) {
            $this->label = ucfirst($this->name);
        }
        return $this->label;
    }
}