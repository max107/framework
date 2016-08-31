<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:19
 */

namespace Mindy\Form\Widget;

use Mindy\Form\Widget;

class UEditorWidget extends Widget
{
    /**
     * @return string
     */
    public function render()
    {
        $field = $this->getField();
        $js = "<script type='text/javascript'>var ue = UE.getEditor('{$field->getHtmlId()}');</script>";
        return $field->renderInput() . $js;
    }
}