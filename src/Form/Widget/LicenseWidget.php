<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 11:48
 */

namespace Mindy\Form\Widget;

use Mindy\Form\Widget;

class LicenseWidget extends Widget
{
    public $content = '';

    public function render()
    {
        $field = $this->getField();
        if ($field) {
            return $this->content . ' ' . $field->renderInput();
        }
        return $this->content;
    }
}