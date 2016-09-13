<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:20
 */

namespace Mindy\Tests\Form;

use Mindy\Form\Fields\CheckboxField;
use Mindy\Form\Fields\ColorField;
use Mindy\Form\Fields\DateField;
use Mindy\Form\Fields\DateTimeField;
use Mindy\Form\Fields\EmailField;
use Mindy\Form\Fields\MonthField;
use Mindy\Form\Fields\NumberField;
use Mindy\Form\Fields\RadioField;
use Mindy\Form\Fields\RangeField;
use Mindy\Form\Fields\SearchField;
use Mindy\Form\Fields\SelectField;
use Mindy\Form\Fields\TelField;
use Mindy\Form\Fields\TextField;
use Mindy\Form\Fields\TimeField;
use Mindy\Form\Fields\UrlField;
use Mindy\Form\Fields\WeekField;
use Mindy\Form\Form;
use Mindy\Tests\Form\Forms\FooFieldsetForm;
use Mindy\Tests\Form\Forms\FooForm;
use Mindy\Tests\Form\Forms\FooMockModel;
use Mindy\Tests\Form\Forms\FooModelForm;
use Symfony\Component\Validator\Constraints as Assert;

class NewFormTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Form::$ids = [];
    }

    public function testCheckbox()
    {
        $form = new FooFieldsetForm();
        $field = new CheckboxField([
            'choices' => [1 => 1, 2 => 2, 3 => 3]
        ]);
        $field->setForm($form);
        $field->setName('test');
        $html = $field->render();
        $this->assertContains("<label for='FooFieldsetForm_1_test_0'>1</label>", $html);
        $this->assertContains("<label for='FooFieldsetForm_1_test_1'>2</label>", $html);
        $this->assertContains("<label for='FooFieldsetForm_1_test_2'>3</label>", $html);
        $this->assertContains("<input type='checkbox' id='FooFieldsetForm_1_test_0' value='1' name='FooFieldsetForm[test]'/>", $html);
        $this->assertContains("<input type='checkbox' id='FooFieldsetForm_1_test_1' value='2' name='FooFieldsetForm[test]'/>", $html);
        $this->assertContains("<input type='checkbox' id='FooFieldsetForm_1_test_2' value='3' name='FooFieldsetForm[test]'/>", $html);
    }

    public function testRadio()
    {
        $form = new FooFieldsetForm();
        $field = new RadioField([
            'choices' => [1 => 1, 2 => 2, 3 => 3]
        ]);
        $field->setForm($form);
        $field->setName('test');
        $html = $field->render();
        $this->assertContains("<label for='FooFieldsetForm_1_test_0'>1</label>", $html);
        $this->assertContains("<label for='FooFieldsetForm_1_test_1'>2</label>", $html);
        $this->assertContains("<label for='FooFieldsetForm_1_test_2'>3</label>", $html);
        $this->assertContains("<input type='radio' id='FooFieldsetForm_1_test_0' value='1' name='FooFieldsetForm[test]'/>", $html);
        $this->assertContains("<input type='radio' id='FooFieldsetForm_1_test_1' value='2' name='FooFieldsetForm[test]'/>", $html);
        $this->assertContains("<input type='radio' id='FooFieldsetForm_1_test_2' value='3' name='FooFieldsetForm[test]'/>", $html);
    }

    public function testSelect()
    {
        $form = new FooFieldsetForm();
        $field = new SelectField([
            'choices' => [1, 2, 3]
        ]);
        $field->setForm($form);
        $field->setName('test');
        $html = $field->render();
        $this->assertContains("<input type='hidden' value='' name='FooFieldsetForm[test]' />", $html);
        $this->assertContains("<select id='FooFieldsetForm_1_test' name='FooFieldsetForm[test]'>", $html);
        $this->assertContains("<option value='0'>1</option><option value='1'>2</option><option value='2'>3</option>", $html);
    }

    public function testFieldsets()
    {
        $form = new FooFieldsetForm();
        $html = $form->render();
        $this->assertContains('<legend>test</legend>', $html);
    }

    public function testRender()
    {
        $form = new FooForm();
        $field = $form['name'];
        $html = $field->render();
        $this->assertContains("for='FooForm_1_name'", $html);
        $this->assertContains('required', $html);
        $this->assertContains("id='FooForm_1_name'", $html);
        $this->assertContains("name='FooForm[name]'", $html);
        $this->assertContains("form-input-text", $html);
        $this->assertContains("form-input-errors", $html);
    }

    public function htmlAttributesProvider()
    {
        return [
            [['id' => 'foo'], 'id="foo"'],
            [['required' => true], 'required="true"'],
            [['id' => 'foo', 'type' => 'datetime'], 'id="foo" type="datetime"']
        ];
    }

    /**
     * @dataProvider htmlAttributesProvider
     */
    public function testHtmlAttributes(array $html, string $htmlString)
    {
        $field = new TextField(['html' => $html]);
        $this->assertEquals($htmlString, $field->getHtmlAttributes());
    }

    public function testHtmlInputs()
    {
        $types = [
            ColorField::class => 'color',
            TelField::class => 'tel',
            MonthField::class => 'month',
            WeekField::class => 'week',
            UrlField::class => 'url',
            DateField::class => 'date',
            DateTimeField::class => 'datetime',
            NumberField::class => 'number',
            RangeField::class => 'range',
            SearchField::class => 'search',
            TimeField::class => 'time',
            EmailField::class => 'email'
        ];
        foreach ($types as $class => $type) {
            $field = new $class;
            $this->assertInstanceOf($class, $field);
            $this->assertContains($type, $field->template);
        }
    }

    public function testChoices()
    {
        $field = new TextField([
            'required' => false,
            'choices' => [
                1 => 1,
                2 => 2,
                3 => 3
            ],
        ]);
        $field->setValue(4);
        $this->assertFalse($field->isValid());
        $this->assertEquals(['The value you selected is not a valid choice.'], $field->getErrors());
        $field->setValue(1);
        $this->assertTrue($field->isValid());
        $this->assertEquals(0, count($field->getErrors()));
    }

    public function testCustomValidators()
    {
        $field = new TextField([
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
        $field = new TextField(['required' => true]);
        $this->assertFalse($field->isValid());
        $this->assertEquals(['This value should not be blank.'], $field->getErrors());

        $field = new TextField(['required' => false]);
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

    public function testAttributes()
    {
        $form = new FooForm();
        $form->setAttributes(['name' => 'foo']);
        $this->assertEquals(['name' => 'foo'], $form->getAttributes());
    }

    public function testPopulate()
    {
        $form = new FooForm();
        $form->populate(['FooForm' => ['name' => 'foo']]);
        $this->assertEquals(['name' => 'foo'], $form->getAttributes());
    }

    public function testIterator()
    {
        foreach (new FooForm() as $field) {
            $this->assertInstanceOf(TextField::class, $field);
        }
    }

    public function testCount()
    {
        $this->assertEquals(1, count(new FooForm()));
    }

    public function testModelForm()
    {
        $form = new FooModelForm();
        $form->setModel(new FooMockModel());
        $this->assertEquals(['name', 'phone'], array_keys($form->getAttributes()));

        $form->setAttributes(['name' => 'foo', 'phone' => 123456]);
        $this->assertEquals(['name' => null, 'phone' => null], $form->getModel()->getAttributes());
        $this->assertTrue($form->save());
        $this->assertEquals(['name' => 'foo', 'phone' => 123456], $form->getModel()->getAttributes());

        $html = $form->render();
        $this->assertContains("<label for='FooModelForm_1_name'>имя <span class='required'>*</span></label>", $html);
        $this->assertContains("<label for='FooModelForm_1_phone'>телефон <span class='required'>*</span></label>", $html);

        $form = new FooModelForm();
        $model = new FooMockModel();
        $model->setAttributes(['name' => 'foo', 'phone' => 123456]);
        $form->setModel($model);
        $this->assertEquals(['name' => 'foo', 'phone' => 123456], $form->getModel()->getAttributes());
        $form->setModelAttributes(['name' => 'bar']);
        $this->assertEquals(['name' => 'foo', 'phone' => 123456], $form->getAttributes());
        $this->assertEquals(['name' => 'bar', 'phone' => 123456], $form->getModel()->getAttributes());
    }
}