<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 11:48
 */

namespace Mindy\Form\Widget;

use Mindy\Form\FieldInterface;
use Mindy\Form\Widget;

class LicenseWidget extends Widget
{
    /**
     * @var string
     */
    public $content = '';

    /**
     * @param FieldInterface $field
     * @return string
     */
    public function render(FieldInterface $field) : string
    {
        return $this->content . ' ' . $field->render();
    }
}