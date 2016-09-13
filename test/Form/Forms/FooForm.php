<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:24
 */

namespace Mindy\Tests\Form\Forms;

use Mindy\Form\Fields\CharField;
use Mindy\Form\NewForm;

class FooForm extends NewForm
{
    public function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class,
                'required' => true
            ],
        ];
    }
}