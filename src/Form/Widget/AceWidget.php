<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:09
 */

namespace Mindy\Form\Widget;

use Mindy\Form\Widget;
use Mindy\Helper\Json;

class AceWidget extends Widget
{
    /**
     * @var string
     */
    public $aceMode = "ace/mode/twig";
    /**
     * @var string
     */
    public $aceTheme = "ace/theme/crimson_editor";
    /**
     * @var bool
     */
    public $readOnly = false;

    /**
     * @return string
     */
    public function render()
    {
        $field = $this->getField();

        $out = strtr('<div id="{id}-ace-editor" class="ace-editor">{value}</div>
        <script type="text/javascript">
            var editor = ace.edit("{id}-ace-editor");

            // Hide deprecation warning
            editor.$blockScrolling = Infinity;

            editor.setFontSize(".9rem");
            editor.getSession().setTabSize(4);
            editor.setShowPrintMargin(false);
            editor.setAutoScrollEditorIntoView(true);
            editor.setReadOnly({readonly});
            editor.setOption("enableEmmet", true);
            ' . ($this->aceMode ? 'editor.getSession().setMode("' . $this->aceMode . '");' : '') . '
            ' . ($this->aceTheme ? 'editor.setTheme("' . $this->aceTheme . '");' : '') . '
            editor.getSession().on("change", function(e) {
                $("#{id}").val(editor.getSession().getValue());
            });
        </script>', [
            '{id}' => $field->getHtmlId(),
            '{value}' => htmlentities($field->getValue()),
            '{readonly}' => Json::encode($this->readOnly)
        ]);

        return $field->renderInput() . $out;
    }
}