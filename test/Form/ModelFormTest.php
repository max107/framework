<?php

use Mindy\Form\Fields\TextField;
use Mindy\Form\ModelForm;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Model;

class Test extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
        ];
    }

    public static function getModule()
    {
        return null;
    }
}

class TestModelForm extends ModelForm
{
    public function getFields()
    {
        return [
            'content' => [
                'class' => TextField::class,
            ]
        ];
    }

    public function getModel()
    {
        return new Test;
    }
}

/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 12:28
 */
class ModelFormTest extends PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $form = new TestModelForm();
        $this->assertEquals(2, count($form->getFieldsInit()));
        $this->assertNull($form->getInstance());
        $this->assertNotNull($form->getModel());
    }

    public function testInitialAttributes()
    {
        $form = new TestModelForm([
            'attributes' => [
                'name' => 'foo',
                'content' => 'bar'
            ]
        ]);
        $this->assertEquals(['name' => 'foo', 'content' => 'bar'], $form->getAttributes());
    }

    public function testSetAttributes()
    {
        $form = new TestModelForm(['exclude' => ['content']]);
        $form->setAttributes(['name' => 'foo']);
        $this->assertEquals(['name' => 'foo'], $form->getAttributes());

        $form->setAttributes(['name' => 'bar']);
        $this->assertEquals(['name' => 'bar'], $form->getAttributes());
    }
}