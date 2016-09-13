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
use Mindy\Form\ModelForm;
use Mindy\Form\Widget;

class FileWidget extends Widget
{
    /**
     * @var bool
     */
    public $cleanValue = '1';
    /**
     * @var string
     */
    public $currentTemplate = '<p class="current-file-container">{label}:<br/><a class="current-file" href="{current}" target="_blank">{current}</a></p>';
    /**
     * @var string
     */
    public $cleanTemplate = '<label for="{id}-clean" class="clean-label"><input type="checkbox" id="{id}-clean" name="{name}" value="{value}"> {label}</label>';

    /**
     * @param FieldInterface $field
     * @return string
     */
    public function render(FieldInterface $field) : string
    {
        $html = $field->render();
        if ($field->getForm() instanceof ModelForm && $value = $field->getValue()) {
            $currentLink = strtr($this->currentTemplate, [
                '{current}' => $value,
                '{label}' => app()->t('form', "Current file")
            ]);
            if ($field->isRequired()) {
                $clean = '';
            } else {
                $clean = strtr($this->cleanTemplate, [
                    '{id}' => $field->getHtmlId(),
                    '{name}' => $field->getHtmlName(),
                    '{value}' => $this->cleanValue,
                    '{label}' => app()->t('form', "Clean")
                ]);
            }
            return $currentLink . $clean . $html;
        }
        return $html;
    }
}