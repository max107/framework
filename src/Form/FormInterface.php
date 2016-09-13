<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 15:58
 */

namespace Mindy\Form;

interface FormInterface
{
    /**
     * @return int
     */
    public function getId() : int;

    /**
     * @return string
     */
    public function classNameShort() : string;

    /**
     * @param string $name
     * @return FieldInterface
     */
    public function getField(string $name) : FieldInterface;
}