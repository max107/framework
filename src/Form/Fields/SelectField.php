<?php
/**
 * User: max
 * Date: 06/04/16
 * Time: 13:08
 */

namespace Mindy\Form\Fields;

class SelectField extends DropDownField
{
    public $template = "<select id='{id}' name='{name}' {html}>{input}</select>";

    public function render()
    {
        $js = "<script type='text/javascript'>$('#{$this->getHtmlId()}').selectize();</script>";
        return parent::render() . $js;
    }
}