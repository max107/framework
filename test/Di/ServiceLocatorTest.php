<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 17:21
 */

namespace Mindy\Tests\Zend;

use Mindy\Di\ServiceLocator;

class IncrementClass
{
    static public $id = 0;

    public function __construct()
    {
        self::$id += 1;
    }
}

class ServiceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testServices()
    {
        $serviceManager = new ServiceLocator([
            'foo' => [
                'class' => \stdClass::class
            ],
            'bar' => \stdClass::class,
            'baz' => function () {
                return new \stdClass();
            },
            'test' => new \stdClass
        ]);

        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('foo'));
        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('bar'));
        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('baz'));
        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('test'));

        $this->assertTrue($serviceManager->has('foo'));
        $this->assertTrue($serviceManager->has('test'));
    }

    public function testShared()
    {
        $serviceManager = new ServiceLocator([
            'foo' => IncrementClass::class,
        ]);
        $service1 = $serviceManager->get('foo');
        $service2 = $serviceManager->get('foo');

        $this->assertEquals($service1::$id, $service2::$id);
    }
}