<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:51
 */

namespace Mindy\Form;

interface FormModelInterface
{
    /**
     * @return array
     */
    public function getAttributes() : array;
}