<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 08/08/16
 * Time: 11:46
 */

namespace Mindy\Di\Tests;

use Mindy\Di\Container;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;

class Foo
{
    public $number = 1;

    public function __construct($number = 1)
    {
        $this->number = $number;
    }
}

class FooSingleton
{
    private static $id = 0;

    public function __construct()
    {
        self::$id++;
    }

    public function getId()
    {
        return self::$id;
    }
}

class Bar
{
    public $foo;

    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }
}

class BarTraits
{
    use Accessors, Configurator;
}

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $container = new Container;
        $container->set(Foo::class, [
            'class' => Foo::class,
            'number' => 2
        ]);
        $container->set(Bar::class, [
            'class' => Bar::class
        ]);

        $bar = $container->get(Bar::class);
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertInstanceOf(Foo::class, $bar->foo);
        $this->assertEquals(2, $bar->foo->number);
    }

    public function testSingleton()
    {
        $container = new Container;
        $container->setSingleton(FooSingleton::class);

        $this->assertEquals(1, $container->get(FooSingleton::class)->getId());
        $this->assertEquals(1, $container->get(FooSingleton::class)->getId());
        $this->assertEquals(1, $container->get(FooSingleton::class)->getId());
    }

    public function testHas()
    {
        $container = new Container;
        $container->set(Foo::class);
        $container->setSingleton(FooSingleton::class);
        $this->assertTrue($container->has(FooSingleton::class));
        $this->assertFalse($container->has('\DummyAbstract'));
        $this->assertTrue($container->hasSingleton(FooSingleton::class));
        $this->assertFalse($container->hasSingleton('\DummyAbstract'));
    }

    public function testClear()
    {
        $container = new Container;
        $container->set(Foo::class);
        $this->assertTrue($container->has(Foo::class));
        $container->clear(Foo::class);
        $this->assertFalse($container->has(Foo::class));
    }

    public function testBuild()
    {
        $container = new Container;
        $container->get(Foo::class, [
            'number' => 2
        ]);
        $this->assertInstanceOf(Foo::class, $container->get(Foo::class));
    }

    public function testCallableDefinition()
    {
        $container = new Container;
        $container->set(Foo::class, function() {
            return new Foo;
        });
        $this->assertInstanceOf(Foo::class, $container->get(Foo::class));
    }

    public function testObjectDefinition()
    {
        $container = new Container;
        $container->set(Foo::class, new Foo);
        $this->assertInstanceOf(Foo::class, $container->get(Foo::class));
    }

    public function testGetDefinitions()
    {
        $container = new Container;
        $container->set(Foo::class);
        $this->assertEquals([
            Foo::class => ['class' => Foo::class]
        ], $container->getDefinitions());
    }
}