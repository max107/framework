<?php
/**
 * User: max
 * Date: 06/04/16
 * Time: 13:08
 */

namespace Mindy\Form\Fields;

class SelectField extends Field
{
    /**
     * Span tag needed because: http://stackoverflow.com/questions/23920990/firefox-30-is-not-hiding-select-box-arrows-anymore
     * @var string
     */
    public $template = "<span class='select-holder'><select id='{id}' name='{name}'{html}>{input}</select></span>";
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

    public function renderInput() : string
    {
        $name = $this->getHtmlName();
        return implode("\n", ["<input type='hidden' value='' name='{$name}' />", strtr($this->template, [
            '{id}' => $this->getHtmlId(),
            '{input}' => $this->getInputHtml(),
            '{name}' => $this->multiple ? $this->getHtmlName() . '[]' : $this->getHtmlName(),
            '{html}' => $this->getHtmlAttributes()
        ])]);
    }

    protected function getInputHtml()
    {
        $selected = [];

        if ($this->choices instanceof \Closure) {
            $choices = $this->choices->__invoke();
        } else {
            $choices = $this->choices;
        }

        if (empty($choices)) {
            return [];
        }

        if (!$this->required) {
            $choices = ['' => $this->empty] + $choices;
        }

        $value = $this->getValue();
        if ($value) {
            if (is_array($value)) {
                $selected = $value;
            } else {
                $selected[] = $value;
            }
        }

        if ($this->multiple) {
            $this->html['multiple'] = true;
        }
        return $this->generateOptions($choices, $selected, $this->disabled);
    }

    /**
     * @param array $data
     * @param array $selected
     * @param array $disabled
     * @return string
     */
    protected function generateOptions(array $data, array $selected = [], array $disabled = []) : string
    {
        $out = '';
        foreach ($data as $value => $name) {
            $out .= strtr("<option value='{value}'{selected}{disabled}>{name}</option>", [
                '{value}' => $value,
                '{name}' => $name,
                '{disabled}' => in_array($value, $disabled) ? " disabled" : "",
                '{selected}' => in_array($value, $selected) ? " selected='selected'" : ""
            ]);
        };
        return $out;
    }
}