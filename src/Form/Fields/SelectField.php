<?php
/**
 * User: max
 * Date: 06/04/16
 * Time: 13:08
 */

namespace Mindy\Form\Fields;

use Mindy\Form\FormInterface;

/**
 * @method \Mindy\Form\FieldInterface getFormField($fieldClass = '')
 */
class SelectField extends Field
{
    /**
     * Span tag needed because: http://stackoverflow.com/questions/23920990/firefox-30-is-not-hiding-select-box-arrows-anymore
     * @var string
     */
    public $template = "<span class='select-holder'><select id='{id}' name='{name}'{html}>{input}</select></span>";
    /**
     * @var string
     */
    public $empty = '';
    /**
     * @var array
     */
    public $disabled = [];

    /**
     * @param FormInterface $form
     * @return string
     */
    public function renderInput(FormInterface $form) : string
    {
        $name = $this->getHtmlName();
        return implode("\n", ["<input type='hidden' value='' name='{$name}' />", strtr($this->template, [
            '{id}' => $this->getHtmlId(),
            '{input}' => $this->getInputHtml(),
            '{name}' => (isset($this->html['multiple']) && $this->html['multiple']) ? $this->getHtmlName() . '[]' : $this->getHtmlName(),
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
            return '';
        }

        $value = $this->getValue();
        if ($value) {
            if (is_array($value)) {
                $selected = $value;
            } else {
                $selected[] = $value;
            }
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

            if (is_array($name)) {
                $out .= strtr('<optgroup label="{label}">{html}</optgroup>', [
                    '{label}' => $value,
                    '{html}' => $this->generateOptions($name, $selected, $disabled)
                ]);
            } else {
                $out .= strtr("<option value='{value}'{selected}{disabled}>{name}</option>", [
                    '{value}' => $value,
                    '{name}' => $name,
                    '{disabled}' => in_array($value, $disabled) ? " disabled" : "",
                    '{selected}' => in_array($value, $selected) ? " selected='selected'" : ""
                ]);
            }
        };
        return $out;
    }
}