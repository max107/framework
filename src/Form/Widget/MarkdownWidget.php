<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:16
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\FormInterface;
use Mindy\Form\Widget;

class MarkdownWidget extends Widget
{
    /**
     * @param FormInterface $form
     * @param FieldInterface $field
     * @return string
     */
    public function render(FormInterface $form, FieldInterface $field) : string
    {
        $html = '<div id="editor">{$field->renderInput()}</div><div class="content" id="preview"></div>';

        $js = <<<JS
<script type="text/javascript">
    var md = new Remarkable({
        breaks: false,
        html: false,
        typographer: false,
        highlight: function (str, lang) {
            if (lang && hljs.getLanguage(lang)) {
                try {
                    return hljs.highlight(lang, str).value;
                } catch (err) {}
            }

            try {
                return hljs.highlightAuto(str).value;
            } catch (err) {}

            return '';
        }
    });
    var preview = function() {
        var source = $('#{$field->getHtmlId()}').val();
        $('#preview').html(md.render(source));
    };
</script>
JS;
        return $field->renderInput($form) . $html . $js;
    }
}