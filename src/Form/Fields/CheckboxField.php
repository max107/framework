<?php

namespace Mindy\Form\Fields;

/**
 * Class CheckboxField
 * @package Mindy\Form
 */
class CheckboxField extends CharField
{
    public $template = "<input type='{type}' id='{id}' value='{value}' name='{name}'{html}/>";

    /**
     * Template for container choices
     * ex: "<span>{input}</span>"
     * @var string
     */
    public $container = '{input}';

    public $type = "checkbox";

    public function render()
    {
        $label = $this->renderLabel();
        $input = $this->renderInput();
        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();

        if (empty($this->choices)) {
            return implode("\n", [
                "<input type='hidden' value='' name='" . $this->getHtmlName() . "' />",
                $input, $label, $hint, $errors
            ]);
        } else {
            return implode("\n", [
                "<input type='hidden' value='' name='" . parent::getHtmlName() . "' />",
                $label, $input, $hint, $errors
            ]);
        }
    }

    public function getHtmlName()
    {
        return $this->getPrefix() . '[' . $this->name . ']' . ($this->choices ? '[]' : '');
    }

    public function renderInput()
    {
        if (!empty($this->choices)) {
            $inputs = [];
            $i = 0;
            $values = $this->value;

            if (!is_array($values)) {
                if ($values) {
                    $values = [$values];
                } else {
                    $values = [];
                }
            }

            foreach ($this->choices as $value => $labelStr) {
                $label = strtr("<label for='{for}'>{label}</label>", [
                    '{for}' => $this->getHtmlId() . '_' . $i,
                    '{label}' => $labelStr
                ]);

                $html = $this->getHtmlAttributes();
                if (is_array($values) && in_array($value, $values)) {
                    if ($html) {
                        $html .= ' ';
                    }
                    $html .= 'checked="checked"';
                }

                $input = strtr($this->template, [
                    '{type}' => $this->type,
                    '{id}' => $this->getHtmlId() . '_' . $i,
                    '{name}' => $this->getHtmlName(),
                    '{value}' => $value,
                    '{html}' => $html
                ]);
                $i++;
                $contained = strtr($this->container, [
                    '{input}' => implode("\n", [$input, $label])
                ]);
                $inputs[] = $contained;
            }
            return implode("\n", $inputs);
        } else {
            if ($this->value) {
                $this->html['checked'] = 'checked';
            }
            $input = strtr($this->template, [
                '{type}' => $this->type,
                '{id}' => $this->getHtmlId(),
                '{name}' => $this->getHtmlName(),
                '{value}' => 1,
                '{html}' => $this->getHtmlAttributes()
            ]);

            return $input;
        }
    }
}
