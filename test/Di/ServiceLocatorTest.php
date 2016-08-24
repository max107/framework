<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 08/08/16
 * Time: 11:33
 */

namespace Mindy\Di\Tests;

use Exception;
use Mindy\Di\ServiceLocator;

class Example
{
}

class Dummy
{
}

class ConstructorArgument
{
    private $_handlers = [];

    public function __construct($handlers = [])
    {
        $this->_handlers = $handlers;
    }

    public function getHandlers()
    {
        return $this->_handlers;
    }
}

class ServiceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $locator = new ServiceLocator([
            'example' => [
                'class' => Example::class
            ]
        ]);
        $this->assertInstanceOf(Example::class, $locator->get('example'));
        $this->assertInstanceOf(Example::class, $locator->example);
    }

    public function testSetComponents()
    {
        $locator = new ServiceLocator();
        $locator->setComponents([
            'example' => [
                'class' => Example::class
            ]
        ]);
        $this->assertInstanceOf(Example::class, $locator->get('example'));
        $this->assertInstanceOf(Example::class, $locator->example);
    }

    public function testUnknown()
    {
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('example', false));
    }

    public function testUnknownException()
    {
        $this->setExpectedException(Exception::class);
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('example'));
    }

    public function testSetArray()
    {
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('dummy', false));
        $locator->set('dummy', [
            'class' => Dummy::class
        ]);
        $this->assertInstanceOf(Dummy::class, $locator->dummy);
    }

    public function testSetString()
    {
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('dummy', false));
        $locator->set('dummy', Dummy::class);
        $this->assertInstanceOf(Dummy::class, $locator->dummy);
    }

    public function testSetClosure()
    {
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('dummy', false));
        $locator->set('dummy', function() {
            return new Dummy;
        });
        $this->assertInstanceOf(Dummy::class, $locator->dummy);
    }

    public function testSetObject()
    {
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('dummy', false));
        $locator->set('dummy', new Dummy);
        $this->assertInstanceOf(Dummy::class, $locator->dummy);
    }

    public function testClear()
    {
        $locator = new ServiceLocator();
        $this->assertNull($locator->get('dummy', false));
        $locator->set('dummy', new Dummy);
        $this->assertInstanceOf(Dummy::class, $locator->dummy);
        $locator->clear('dummy');
        $this->assertNull($locator->get('dummy', false));
    }

    public function testGetComponents()
    {
        $locator = new ServiceLocator([
            'example' => ['class' => Example::class],
            'dummy' => Dummy::class
        ]);
        $this->assertEquals(2, count($locator->getComponents(true)));
        $this->assertEquals(0, count($locator->getComponents(false)));
        $dummy = $locator->dummy;
        $this->assertEquals(2, count($locator->getComponents(true)));
        $this->assertEquals(1, count($locator->getComponents(false)));
    }

    public function testConstructorArgument()
    {
        $locator = new ServiceLocator([
            'example' => ['class' => ConstructorArgument::class, [1, 2, 3]]
        ]);
        $this->assertEquals([1, 2, 3], $locator->example->getHandlers());
    }
}