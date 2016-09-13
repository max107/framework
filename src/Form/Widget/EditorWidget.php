<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:13
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\FormInterface;
use Mindy\Form\ModelForm;
use Mindy\Form\Widget;
use Mindy\Helper\JavaScript;

class EditorWidget extends Widget
{
    /**
     * @var string
     */
    public $uploadUrl = '';

    /**
     * @param FormInterface $form
     * @return string
     */
    protected function getUploadUrl(FormInterface $form) : string
    {
        if (empty($this->uploadUrl) && $form instanceof ModelForm) {
            return '/core/files/upload/?path=' . $form->getModel()->getModuleName() . '/' . $form->getModel()->classNameShort();
        }
        return $this->uploadUrl;
    }

    /**
     * @param FieldInterface $field
     * @return string
     */
    public function render(FieldInterface $field) : string
    {
        $options = JavaScript::encode([
            'language' => 'ru',
            'plugins' => ['space', 'text', 'image', 'video'],
            'image' => [
                'uploadUrl' => $this->getUploadUrl($field->getForm())
            ]
        ]);
        $js = "<script type='text/javascript'>var editor = meditor.init('#{$field->getHtmlId()}', $options);</script>";
        return $field->renderInput() . $js;
    }
}