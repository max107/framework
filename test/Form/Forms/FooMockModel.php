<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 21:54
 */

namespace Mindy\Tests\Form\Forms;

use Mindy\Form\FormModelInterface;

class FooMockModel implements FormModelInterface
{
    protected $attributes = [
        'name' => null,
        'phone' => null,
    ];

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getFieldsInit() : array
    {
        return [
            'name' => new FooMockField('name', 'имя', $this),
            'phone' => new FooMockField('phone', 'телефон', $this),
        ];
    }

    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public function save()
    {
        return true;
    }
}