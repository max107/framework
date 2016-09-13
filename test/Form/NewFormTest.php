<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:20
 */

namespace Mindy\Tests\Form;

use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\EmailField;
use Mindy\Form\Fields\NewField;
use Mindy\Tests\Form\Forms\FooForm;
use Symfony\Component\Validator\Constraints as Assert;

class NewFormTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomValidators()
    {
        $field = new CharField([
            'required' => false,
            'validators' => [
                new Assert\NotBlank()
            ]
        ]);
        $this->assertFalse($field->isValid());
        $this->assertEquals(['This value should not be blank.'], $field->getErrors());
    }

    public function testEmailField()
    {
        $field = new EmailField(['required' => true]);
        $this->assertFalse($field->isValid());
        $this->assertEquals(['This value should not be blank.'], $field->getErrors());

        $field->setValue('user@mail.com');
        $this->assertTrue($field->isValid());
        $this->assertEquals([], $field->getErrors());

        $field->setValue('user_blabla');
        $this->assertFalse($field->isValid());
        $this->assertEquals(['This value is not a valid email address.'], $field->getErrors());

        $field = new EmailField(['required' => false]);
        $this->assertTrue($field->isValid());
    }

    public function testField()
    {
        $field = new NewField(['required' => true]);
        $this->assertFalse($field->isValid());
        $this->assertEquals(['This value should not be blank.'], $field->getErrors());

        $field = new NewField(['required' => false]);
        $this->assertTrue($field->isValid());
    }
    
    public function testForm()
    {
        $form = new FooForm();
        $this->assertEquals(1, count($form->getFields()));
        $this->assertFalse($form->isValid());
        $this->assertEquals([
            'name' => ['This value should not be blank.']
        ], $form->getErrors());
    }
}