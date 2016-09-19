<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 17:21
 */

namespace Mindy\Tests\Zend;

use Mindy\Di\ServiceManager;

class ServiceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testServices()
    {
        $serviceManager = new ServiceManager([
            'foo' => [
                'class' => \stdClass::class
            ],
            'bar' => \stdClass::class,
            'baz' => function () {
                return new \stdClass();
            }
        ]);

        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('foo'));
        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('bar'));
        $this->assertInstanceOf(\stdClass::class, $serviceManager->get('baz'));
    }
}