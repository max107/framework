<?php

namespace Mindy\Form\Fields;

/**
 * Class CheckboxField
 * @package Mindy\Form
 */
class CheckboxField extends Field
{
    /**
     * @var string
     */
    public $containerTemplate = '{input}{label}{hint}{errors}';
    /**
     * @var string
     */
    public $template = "<input type='checkbox' id='{id}' value='{value}' name='{name}'{html}/>";

    /**
     * Template for container choices
     * ex: "<span>{input}</span>"
     * @var string
     */
    public $container = '{input}';

    public function renderInput() : string
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
                '{id}' => $this->getHtmlId(),
                '{name}' => $this->getHtmlName(),
                '{value}' => 1,
                '{html}' => $this->getHtmlAttributes()
            ]);

            return $input;
        }
    }
}
