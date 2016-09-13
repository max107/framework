<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:19
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\Widget;

class UEditorWidget extends Widget
{
    /**
     * @param FieldInterface $field
     * @return string
     */
    public function render(FieldInterface $field) : string
    {
        $js = "<script type='text/javascript'>var ue = UE.getEditor('{$field->getHtmlId()}');</script>";
        return $field->renderInput() . $js;
    }
}