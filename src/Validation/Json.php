<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 09:56
 */

namespace Mindy\Validation;

use Symfony\Component\Validator\Constraint;

class Json extends Constraint
{
    public $message = 'Not json serialize object: %type%';
}