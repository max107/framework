<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 22:46
 */

namespace Mindy\Form\Widget;

use function Mindy\app;
use Mindy\Form\FieldInterface;
use Mindy\Form\FormInterface;
use Mindy\Form\ModelForm;
use Mindy\Form\Widget;
use function Mindy\trans;

class FileWidget extends Widget
{
    /**
     * @var bool
     */
    public $cleanValue = '1';
    /**
     * @var string
     */
    public $currentTemplate = '<p class="current-file-container"><a class="current-file" href="{current}" target="_blank">{current}</a></p>';
    /**
     * @var string
     */
    public $cleanTemplate = '<label for="{id}-clean" class="clean-label"><input type="checkbox" id="{id}-clean" name="{name}" value="{value}"> {label}</label>';

    /**
     * @param FormInterface $form
     * @param FieldInterface $field
     * @return string
     */
    public function render(FormInterface $form, FieldInterface $field) : string
    {
        $html = $field->renderInput($form);
        if ($form instanceof ModelForm && $value = $field->getValue()) {
            $currentLink = strtr($this->currentTemplate, [
                '{current}' => $value,
            ]);

            if ($field->isRequired()) {
                $clean = '';
            } else {
                $clean = strtr($this->cleanTemplate, array(
                    '{id}' => $field->getHtmlId(),
                    '{name}' => $field->getHtmlName(),
                    '{value}' => $this->cleanValue,
                    '{label}' => trans('framework.form', "Clean")
                ));
            }

            return $currentLink . $clean . $html;
        }
        return $html;
    }
}