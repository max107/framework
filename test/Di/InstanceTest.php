<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 08/08/16
 * Time: 12:08
 */

namespace Mindy\Di\Tests;

use Exception;
use Mindy\Di\Instance;
use Mindy\Di\ServiceLocator;

class InstanceTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $container = new ServiceLocator([
            'foo' => [
                'class' => Foo::class,
                'number' => 2
            ]
        ]);
        $instance = Instance::ensure('foo', Foo::class, $container);
        $this->assertInstanceOf(Foo::class, $instance);
    }

    public function testException()
    {
        $container = new ServiceLocator([
            'foo' => [
                'class' => Foo::class,
                'number' => 2
            ]
        ]);
        $this->setExpectedException(Exception::class);
        $instance = Instance::ensure('foo', Bar::class, $container);
        $this->assertInstanceOf(Foo::class, $instance);
    }

    public function testEmptyException()
    {
        $container = new ServiceLocator([
            'foo' => [
                'class' => Foo::class,
                'number' => 2
            ]
        ]);
        $this->setExpectedException(Exception::class);
        $instance = Instance::ensure(Instance::ensure(null, Bar::class, $container), Bar::class, $container);
        $this->assertInstanceOf(Foo::class, $instance);
    }

    public function testInstance()
    {
        $container = new ServiceLocator([
            'foo' => [
                'class' => Foo::class,
                'number' => 2
            ]
        ]);
        $instance = Instance::ensure(new Foo, Foo::class, $container);
        $this->assertInstanceOf(Foo::class, $instance);
    }

    public function testFetcher()
    {
        $container = new ServiceLocator([
            'foo' => [
                'class' => Foo::class,
                'number' => 2
            ]
        ]);
        $instance = Instance::of('foo', function ($id) use ($container) {
            return $container->get($id);
        });
        $this->assertInstanceOf(Foo::class, $instance->get());
        $this->assertInstanceOf(Foo::class, $instance->get($container));
    }

    public function testFetcherEmpty()
    {
        $instance = Instance::of('foo');
        $this->assertNull($instance->get());
    }

    public function testInvalidType()
    {
        $this->setExpectedException(Exception::class);
        Instance::ensure(new Example, Foo::class);
    }
}