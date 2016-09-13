<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:24
 */

namespace Mindy\Tests\Form\Forms;

use Mindy\Form\Fields\TextField;
use Mindy\Form\Form;

class FooForm extends Form
{
    public function getFields() : array
    {
        return [
            'name' => [
                'class' => TextField::class,
                'required' => true
            ],
        ];
    }
}