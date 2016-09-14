<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 17:38
 */

namespace Mindy\Validation;

interface ValidationAwareInterface
{
    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return array
     */
    public function getValidationConstraints() : array;
}