<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:11
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\FormInterface;
use Mindy\Form\Widget;
use Mindy\Helper\Json;

class CodeMirrorWidget extends Widget
{
    /**
     * @var array
     */
    private $_defaultOptions = [
        'lineNumbers' => true,
        'mode' => ['name' => "jinja2", 'htmlMode' => true],
        'styleActiveLine' => true,
        'matchBrackets' => true,
        'theme' => 'material'
    ];
    /**
     * @var array
     */
    public $options = [];

    /**
     * @param FormInterface $form
     * @param FieldInterface $field
     * @return string
     */
    public function render(FormInterface $form, FieldInterface $field) : string
    {
        $jsOptions = Json::encode(array_merge($this->_defaultOptions, $this->options));
        $js = '<script type="text/javascript">
            var editor = CodeMirror.fromTextArea(document.getElementById("' . $field->getHtmlId() . '"), ' . $jsOptions . ');
        </script>';
        return $field->renderInput($form) . $js;
    }
}