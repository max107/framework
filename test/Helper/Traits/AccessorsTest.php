<?php

namespace Mindy\Helper\Tests\Traits;

use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use PHPUnit_Framework_TestCase;


/**
 * @group base
 */
class AccessorsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var NewAccessorsObject
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new NewAccessorsObject;
    }

    protected function tearDown()
    {
        $this->object = null;
    }

    public function testHasProperty()
    {
        $this->assertTrue($this->object->hasProperty('Text'));
        $this->assertTrue($this->object->hasProperty('text'));
        $this->assertFalse($this->object->hasProperty('Caption'));
        $this->assertTrue($this->object->hasProperty('content'));
        $this->assertFalse($this->object->hasProperty('content', false));
        $this->assertFalse($this->object->hasProperty('Content'));
    }

    public function testCanGetProperty()
    {
        $this->assertTrue($this->object->canGetProperty('Text'));
        $this->assertTrue($this->object->canGetProperty('text'));
        $this->assertFalse($this->object->canGetProperty('Caption'));
        $this->assertTrue($this->object->canGetProperty('content'));
        $this->assertFalse($this->object->canGetProperty('content', false));
        $this->assertFalse($this->object->canGetProperty('Content'));
    }

    public function testCanSetProperty()
    {
        $this->assertTrue($this->object->canSetProperty('Text'));
        $this->assertTrue($this->object->canSetProperty('text'));
        $this->assertFalse($this->object->canSetProperty('Object'));
        $this->assertFalse($this->object->canSetProperty('Caption'));
        $this->assertTrue($this->object->canSetProperty('content'));
        $this->assertFalse($this->object->canSetProperty('content', false));
        $this->assertFalse($this->object->canSetProperty('Content'));
    }

    public function testGetProperty()
    {
        $this->assertTrue('default' === $this->object->Text);
        $this->setExpectedException('Mindy\Exception\UnknownPropertyException');
        $value2 = $this->object->Caption;
    }

    public function testSetProperty()
    {
        $value = 'new value';
        $this->object->Text = $value;
        $this->assertEquals($value, $this->object->Text);
        $this->setExpectedException('Mindy\Exception\UnknownPropertyException');
        $this->object->NewMember = $value;
    }

    public function testSetReadOnlyProperty()
    {
        $this->setExpectedException('Mindy\Exception\InvalidCallException');
        $this->object->object = 'test';
    }

    public function testIsset()
    {
        $this->assertTrue(isset($this->object->text));
        $this->assertFalse(empty($this->object->text));

        $this->object->text = '';
        $this->assertTrue(isset($this->object->text));
        $this->assertTrue(empty($this->object->text));

        $this->object->text = null;
        $this->assertFalse(isset($this->object->text));
        $this->assertTrue(empty($this->object->text));

        $this->assertFalse(isset($this->object->unknownProperty));
        $this->assertTrue(empty($this->object->unknownProperty));
    }

    public function testUnset()
    {
        unset($this->object->text);
        $this->assertFalse(isset($this->object->text));
        $this->assertTrue(empty($this->object->text));
    }

    public function testUnsetReadOnlyProperty()
    {
        $this->setExpectedException('Mindy\Exception\InvalidCallException');
        unset($this->object->object);
    }

    public function testCallUnknownMethod()
    {
        $this->setExpectedException('Mindy\Exception\UnknownMethodException');
        $this->object->unknownMethod();
    }

    public function testArrayProperty()
    {
        $this->assertEquals([], $this->object->items);
        // the following won't work
        /*
        $this->object->items[] = 1;
        $this->assertEquals([1], $this->object->items);
        */
    }

    public function testObjectProperty()
    {
        $this->assertTrue($this->object->object instanceof NewAccessorsObject);
        $this->assertEquals('object text', $this->object->object->text);
        $this->object->object->text = 'new text';
        $this->assertEquals('new text', $this->object->object->text);
    }

    public function testConstruct()
    {
        $object = new NewAccessorsObject(['text' => 'test text']);
        $this->assertEquals('test text', $object->getText());
    }

    public function testHasMethod()
    {
        $object = new NewAccessorsObject(['text' => 'test text']);
        $this->assertTrue($object->hasMethod('getText'));
        $this->assertFalse($object->hasMethod('text'));
    }

    /**
     * @expectedException Mindy\Exception\InvalidCallException
     * @expectedMessage Setting read-only property: NewAccessorsObject::items
     */
    public function testSetterInvalidCallException()
    {
        $object = new NewAccessorsObject();
        $object->items = true;
    }

    /**
     * @expectedException Mindy\Exception\InvalidCallException
     * @expectedMessage Getting write-only property: NewAccessorsObject::data
     */
    public function testGetterInvalidCallException()
    {
        $object = new NewAccessorsObject();
        $data = $object->data;
    }

    public function testClassName()
    {
        $obj = new NewAccessorsObject();
        $this->assertEquals(NewAccessorsObject::class, $obj->className());
    }
}


class NewAccessorsObject
{
    use Accessors, Configurator;

    private $_object = null;
    private $_text = 'default';
    private $_items = [];
    private $_data = [];
    public $content;

    public function getText()
    {
        return $this->_text;
    }

    public function setText($value)
    {
        $this->_text = $value;
    }

    public function getObject()
    {
        if (!$this->_object) {
            $this->_object = new self;
            $this->_object->_text = 'object text';
        }
        return $this->_object;
    }

    public function getExecute()
    {
        return function ($param) {
            return $param * 2;
        };
    }

    public function getItems()
    {
        return $this->_items;
    }

    public function setData($data)
    {
        $this->_data = $data;
    }
}
