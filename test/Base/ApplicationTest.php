<?php

namespace Mindy\Tests\Base;

use Mindy\Base\Mindy;
use Monolog\Logger;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mindy::setApplication(null);
    }

    public function testSimple()
    {
        $params = [
            'foo' => 'bar',
            'one' => ['two' => ['three' => 'yea']]
        ];
        clearstatcache();
        $app = new TestApplication([
            'params' => $params,
            'basePath' => __DIR__ . '/app'
        ]);

        // Unique id test
        $this->assertNotNull($app->getId());

        // Base paths test
        $this->assertEquals(realpath(__DIR__ . '/app'), $app->getBasePath());
        $this->assertEquals(realpath(__DIR__ . '/app/Modules'), $app->getModulePath());
        $this->assertEquals(realpath(__DIR__ . '/app/runtime'), $app->getRuntimePath());

        // Params test
        $this->assertEquals($params, $app->getParams());
        $this->assertEquals('bar', $app->getParam('foo'));
        $this->assertEquals('yea', $app->getParam('one.two.three'));
        $this->assertEquals(false, $app->getParam('one.two.example', false));
    }

    public function testLogger()
    {
        $app = new TestApplication([
            'basePath' => __DIR__ . '/app'
        ]);
        $this->assertInstanceOf(Logger::class, $app->getLogger());
        $app->getLogger()->error('test', [1, 2, 3]);

        $files = glob($app->getRuntimePath() . DIRECTORY_SEPARATOR . 'application*');
        $this->assertEquals(1, count($files));
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
