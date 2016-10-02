<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 22:40
 */

namespace Mindy\Form\Widget;

use function Mindy\app;

class ImageWidget extends FileWidget
{
    public $currentTemplate = '<p class="current-file-container"><a class="current-file" href="{current}" target="_blank"><img src="{current}" alt="{current}" /></a></p>';
}