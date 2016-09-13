<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 19:35
 */

namespace Mindy\Form\Fields;

class TelField extends TextField
{
    /**
     * @var string
     */
    public $template = "<input type='tel' value='{value}' id='{id}' name='{name}'{html}/>";
}