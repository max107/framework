<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:21
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\Widget;
use Mindy\Helper\JavaScript;
use Mindy\Helper\JavaScriptExpression;

class DateTimeWidget extends Widget
{
    /**
     * @var array
     */
    public $options = [];
    /**
     * @var array
     */
    private $_defaultOptions = [
        'showTime' => false,
        'showSeconds' => false,
        'use24hour' => true,
        'incrementHourBy' => 1,
        'incrementMinuteBy' => 1,
        'incrementSecondBy' => 1
    ];

    /**
     * @param FieldInterface $field
     * @return string
     */
    public function render(FieldInterface $field) : string
    {
        $options = array_merge($this->_defaultOptions, $this->options);
        $jsOptions = JavaScript::encode(array_merge($options, [
            'field' => new JavaScriptExpression('document.getElementById("' . $field->getHtmlId() . '")')
        ]));
        $js = "<script type='text/javascript'>new Pikaday($jsOptions);</script>";
        return $field->renderInput() . $js;
    }
}