<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:55
 */

namespace Mindy\Tests\Form\Forms;

use Mindy\Form\Fields\TextField;
use Mindy\Form\FormModelInterface;

class FooMockField
{
    public function __construct(string $name, string $label, FormModelInterface $model)
    {
        $this->name = $name;
        $this->label = $label;
        $this->model = $model;
    }

    /**
     * @return string
     */
    protected function getName() : string
    {
        return $this->name;
    }

    public function getFormField()
    {
        $field = new TextField([
            'label' => $this->label,
            'value' => $this->model->getAttributes()[$this->name]
        ]);
        return $field;
    }
}