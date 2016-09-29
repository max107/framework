<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:54
 */

namespace Mindy\Form\Fields;

use Closure;
use Mindy\Form\FieldInterface;
use Mindy\Form\FormInterface;
use Mindy\Form\WidgetInterface;
use Mindy\Creator\Creator;
use Mindy\Validation\ValidationAwareInterface;
use Mindy\Validation\ValidationAwareTrait;
use Symfony\Component\Validator\Constraints as Assert;

abstract class Field implements FieldInterface, ValidationAwareInterface
{
    use ValidationAwareTrait;

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
     * @var bool
     */
    protected $required = true;
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
    public $label = '';
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
     * @var string
     */
    protected $htmlId;
    /**
     * @var string
     */
    protected $htmlName;
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
        $this->configure($config);
    }

    /**
     * @param array $config
     */
    public function configure(array $config)
    {
        foreach ($config as $key => $value) {
            if (method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($value);
            } else if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
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
        if ($this->escape) {
            if (is_array($this->value)) {
                return array_map(function ($value) {
                    return htmlspecialchars($value, ENT_QUOTES);
                }, $this->value);
            } else {
                return htmlspecialchars($this->value, ENT_QUOTES);
            }
        }

        return $this->value;
    }

    /**
     * @return array
     */
    public function getValidationConstraints() : array
    {
        $constraints = [];

        if ($this->required) {
            $constraints[] = new Assert\NotBlank();
        }

        if ($this->required && !empty($this->choices)) {
            $constraints[] = new Assert\Choice([
                'choices' => array_keys($this->choices instanceof Closure ? $this->choices->__invoke() : $this->choices)
            ]);
        }

        return array_merge($constraints, $this->validators);
    }

    /**
     * @return mixed|string
     */
    public function getHtmlId() : string
    {
        if (isset($this->html['id'])) {
            return $this->html['id'];
        } else {
            return $this->htmlId;
        }
    }

    /**
     * @param FormInterface $form
     */
    protected function setHtmlId(FormInterface $form)
    {
        $this->htmlId = implode('_', [$form->classNameShort(), $form->getId(), $this->name]);
    }

    /**
     * @param FormInterface $form
     */
    protected function setHtmlName(FormInterface $form)
    {
        $this->htmlName = $form->classNameShort();
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
    public function renderLabel() : string
    {
        if ($this->getLabel() === false) {
            return '';
        }

        $star = " <span class='required'>*</span>";

        return strtr("<label for='{for}'>{label}</label>", [
            '{for}' => $this->getHtmlId(),
            '{label}' => $this->required ? $this->getLabel() . $star : $this->getLabel(),
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
        return $this->htmlName . '[' . $this->name . ']';
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
     * @param FormInterface $form
     * @return string
     */
    public function renderInput(FormInterface $form) : string
    {
        if (empty($this->widget) === false && $this->_renderWidget) {
            $this->setRenderWidget(false);
            $input = $this->createWidget()->render($form, $this);
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
     * @param FormInterface $form
     * @return string
     */
    public function render(FormInterface $form) : string
    {
        $this->setHtmlId($form);
        $this->setHtmlName($form);

        return strtr($this->containerTemplate, [
            '{label}' => $this->renderLabel(),
            '{input}' => $this->renderInput($form),
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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}