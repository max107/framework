<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:10
 */

namespace Mindy\Tests\Form\Forms;

class FooFieldsetForm extends FooForm
{
    public function getFieldsets() : array
    {
        return [
            'test' => ['name']
        ];
    }
}