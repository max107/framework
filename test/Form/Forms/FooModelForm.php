<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:50
 */

namespace Mindy\Tests\Form\Forms;

use Mindy\Form\Fields\TextField;
use Mindy\Form\ModelForm;

class FooModelForm extends ModelForm
{
    public function getFields()
    {
        return [
            'name' => [
                'class' => TextField::class,
                'label' => 'test',
            ]
        ];
    }
}