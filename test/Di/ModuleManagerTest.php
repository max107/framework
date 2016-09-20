<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 20:34
 */

namespace Mindy\Tests\Zend;

require(__DIR__ . '/Modules/Test/TestModule.php');

use Mindy\Base\Module;
use Mindy\Di\ModuleManager;
use Mindy\Di\ServiceLocator;

class ModuleManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $serviceManager = new ServiceLocator([
            'foo' => [
                'class' => \stdClass::class
            ],
            'bar' => \stdClass::class,
            'baz' => function () {
                return new \stdClass();
            }
        ]);

        $manager = new ModuleManager([
            'Test' => [
                'class' => '\Modules\Test\TestModule',
                'foo' => 'bar'
            ]
        ]);
        $manager->loadModules($serviceManager);
        $this->assertTrue($manager->has('Test'));
        $this->assertInstanceOf(Module::class, $manager->get('Test'));
        $this->assertEquals('Test', $manager->get('Test')->getId());
        $this->assertEquals('bar', $manager->get('Test')->foo);

        $this->assertTrue($serviceManager->has('example'));
    }
}