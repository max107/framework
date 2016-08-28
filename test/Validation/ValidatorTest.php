<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 21/10/14.10.2014 13:26
 */

namespace Mindy\Tests\Validation;

use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Validation\Interfaces\IValidateField;
use Mindy\Validation\Interfaces\IValidateObject;
use Mindy\Validation\RequiredValidator;
use Mindy\Validation\Traits\ValidateField;
use Mindy\Validation\Traits\ValidateObject;

class Field implements IValidateField
{
    use Accessors, Configurator, ValidateField;

    /**
     * @var
     */
    private $_value;

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    /**
     * @return mixed the value for validation
     */
    public function getValue()
    {
        return $this->_value;
    }
}

class Form implements IValidateObject
{
    use Accessors, Configurator, ValidateObject;

    /**
     * @var array
     */
    public $fields = [];
    /**
     * @var array
     */
    private $_fields = [];

    public function init()
    {
        foreach ($this->fields as $name => $params) {
            $this->_fields[$name] = Creator::createObject($params);
        }
    }

    /**
     * @return mixed the initialized fields for validation
     */
    public function getFieldsInit()
    {
        return $this->_fields;
    }

    /**
     * @param $attribute string
     * @return bool check the field isset
     */
    public function hasField($attribute)
    {
        return array_key_exists($attribute, $this->_fields);
    }

    /**
     * @param $attribute string
     * @return object field instance
     */
    public function getField($attribute)
    {
        return $this->_fields[$attribute];
    }
}

class CleanForm extends Form
{
    public function cleanName($value)
    {
        if (empty($value)) {
            $this->addError('name', 'Error');
        } else {
            return '1' . $value;
        }
    }
}

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidation()
    {
        $v = new RequiredValidator;
        $v->setName('foo');
        $this->assertEquals('foo', $v->getName());
    }

    public function testValidationField()
    {
        $f = new Field([
            'validators' => [
                new RequiredValidator
            ]
        ]);

        $this->assertEquals(1, count($f->validators));
        $this->assertFalse($f->isValid());
        $this->assertTrue($f->hasErrors());
        $this->assertEquals(['Cannot be empty'], $f->getErrors());
        $f->clearErrors();
        $this->assertFalse($f->hasErrors());
        $this->assertEquals([], $f->getErrors());
        $f->setValue(1);
        $this->assertTrue($f->isValid());

        $f->addError('foo');
        $this->assertTrue($f->hasErrors());
        $this->assertEquals(['foo'], $f->getErrors());
        $this->assertTrue($f->isValid());

        $f = new Field([
            'validators' => [
                function ($value) {
                    return 'Error';
                }
            ],
        ]);
        $this->assertFalse($f->isValid());
        $this->assertTrue($f->hasErrors());
        $this->assertEquals(['Error'], $f->getErrors());
    }

    public function testValidationObject()
    {
        $f = new Form([
            'fields' => [
                'name' => [
                    'class' => Field::className(),
                    'validators' => [
                        new RequiredValidator
                    ]
                ]
            ],
        ]);

        $this->assertEquals(1, count($f->getFieldsInit()));
        $this->assertTrue($f->hasField('name'));
        $this->assertInstanceOf(Field::className(), $f->getField('name'));
        $this->assertFalse($f->isValid());
        $this->assertEquals(['name' => ['Cannot be empty']], $f->getErrors());
        $this->assertEquals(['Cannot be empty'], $f->getErrors('name'));
        $this->assertTrue($f->hasErrors());
        $f->clearErrors();
        $this->assertFalse($f->hasErrors());

        $this->assertFalse($f->isValid());
        $f->clearErrors('name');
        $this->assertFalse($f->hasErrors());
    }

    public function testCleanData()
    {
        $f = new CleanForm([
            'fields' => [
                'name' => [
                    'class' => Field::className(),
                    'validators' => [
                        new RequiredValidator
                    ]
                ]
            ],
        ]);
        $this->assertFalse($f->isValid());
        $this->assertEquals(['name' => ['Cannot be empty']], $f->getErrors());
        $this->assertEquals(['Cannot be empty'], $f->getErrors('name'));

        $f->getField('name')->setValue(1);
        $this->assertTrue($f->isValid());
    }
}
