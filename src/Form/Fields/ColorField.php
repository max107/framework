<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 19:32
 */

namespace Mindy\Form\Fields;

class ColorField extends TextField
{
    /**
     * @var string
     */
    public $template = "<input type='color' value='{value}' id='{id}' name='{name}'{html}/>";
}