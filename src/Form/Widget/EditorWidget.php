<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:13
 */

namespace Mindy\Form\Widget;

use Mindy\Form\ModelForm;
use Mindy\Form\Widget;
use Mindy\Helper\JavaScript;

class EditorWidget extends Widget
{
    /**
     * @return string
     */
    public function render()
    {
        $field = $this->getField();

        $image = [];
        $form = $field->getForm();
        if ($form instanceof ModelForm) {
            $model = $form->getModel();
            if ($model) {
                $image = [
                    'uploadUrl' => '/core/files/upload/?path=' . $model->getModuleName() . '/' . $model->classNameShort()
                ];
            }
        }

        $options = JavaScript::encode([
            'language' => 'ru',
            'plugins' => ['space', 'text', 'image', 'video'],
            'image' => $image
        ]);
        $js = "<script type='text/javascript'>var editor = meditor.init('#{$field->getHtmlId()}', $options);</script>";
        return $field->renderInput() . $js;
    }
}