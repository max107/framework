<?php

namespace Mindy\Tests\Form;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Validation\RequiredValidator;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 10:35
 */
class FieldTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mindy::setApplication(null);
        Mindy::getInstance(['name' => 'myapp', 'basePath' => __DIR__]);
    }

    public function tearDown()
    {
        Mindy::setApplication(null);
    }
    
    public function testSetValue()
    {
        $field = new CharField;
        $this->assertNull($field->getValue());
        $field->setValue('foo');
        $this->assertEquals('foo', $field->getValue());
    }

    public function testLabel()
    {
        $field = new CharField(['name' => 'foo']);
        $this->assertEquals("<label for='foo'>Foo</label>", $field->renderLabel());

        $field = new CharField(['name' => 'foo', 'label' => 'Привет']);
        $this->assertEquals("<label for='foo'>Привет</label>", $field->renderLabel());
    }

    public function testRequired()
    {
        $field = new CharField(['required' => true]);
        $field->isValid();
        $this->assertFalse(empty($field->getErrors()));
        $this->assertInstanceOf(RequiredValidator::class, $field->getValidators()[0]);
    }

    public function testValidation()
    {
        $field = new CharField(['validators' =>
            [
                function ($value) {
                    if ($value % 2 == 0) {
                        return true;
                    }
                    return 'Fail';
                }
            ]
        ]);
        $field->setValue(2);
        $field->isValid();
        $this->assertEquals([], $field->getErrors());

        $field->setValue(1);
        $field->isValid();
        $this->assertEquals(['Fail'], $field->getErrors());
    }

    public function testHtml()
    {
        $field = new CharField(['html' => ['name' => 'test', 'id' => 'foo']]);
        $this->assertEquals("name='test'", $field->getHtmlAttributes());

        $field = new CharField(['html' => ['checked' => true]]);
        $this->assertEquals("checked='true'", $field->getHtmlAttributes());

        $field = new CharField(['html' => ['checked']]);
        $this->assertEquals("checked", $field->getHtmlAttributes());
    }

    public function testHint()
    {
        $field = new CharField(['hint' => 'Hello world']);
        $this->assertEquals('<p class="form-hint-text">Hello world</p>', $field->renderHint());
    }

    public function testName()
    {
        $field = new CharField([
            'name' => 'foo',
            'label' => 'Bar',
            'html' => ['id' => 'qwe'],
            'required' => true,
        ]);
        $this->assertEquals("<input type='text' value='' id='qwe' name=''/>", $field->renderInput());
        $this->assertEquals("<label for='qwe'>Bar <span class='required'>*</span></label>", $field->renderLabel());
        $field->isValid();
        $this->assertEquals('<ul class="form-input-errors" id="qwe_errors"><li>Cannot be empty</li></ul>', $field->renderErrors());
    }
}