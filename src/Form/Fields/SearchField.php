<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 19:35
 */

namespace Mindy\Form\Fields;

class SearchField extends TextField
{
    /**
     * @var string
     */
    public $template = "<input type='search' value='{value}' id='{id}' name='{name}'{html}/>";
}