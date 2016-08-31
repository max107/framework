<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:04
 */

namespace Mindy\Form\Widget;

use Mindy\Form\Widget;
use Mindy\Helper\JavaScript;
use Mindy\Helper\JavaScriptExpression;

class RatingWidget extends Widget
{
    public $options = [];
    
    /**
     * @return string
     */
    public function render()
    {
        $field = $this->getField();
        $jsOptions = JavaScript::encode(array_merge([
            'starType' => 'i',
            'numberMax' => 5,
            'score' => $field->getValue(),
            'click' => new JavaScriptExpression('function(score, evt) {
                $("#' . $field->getHtmlId() . '").val(score);
            }')
        ], $this->options));
        $js = "<div id='{$field->getHtmlId()}_rating' class='rating-input'></div><script type='text/javascript'>$('#{$field->getHtmlId()}_rating').raty({$jsOptions});</script>";
        return $field->renderInput() . $js;
    }
}