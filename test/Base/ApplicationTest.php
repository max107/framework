<?php

namespace Mindy\Tests\Base;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Di\ServiceLocator;
use Mindy\Event\EventManager;
use Mindy\Helper\Alias;
use Mindy\Http\Http;
use Mindy\Router\UrlManager;
use Mindy\Security\Security;
use Monolog\Logger;

class FooBarModule extends Module
{
    public $host = 'example.com';
}

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

    public function testDiCoreComponents()
    {
        $app = new TestApplication([
            'basePath' => __DIR__ . '/app'
        ]);

        $this->assertInstanceOf(ServiceLocator::class, $app->getServiceLocator());
        $this->assertInstanceOf(Http::class, $app->getServiceLocator()->get('http'));
        $this->assertInstanceOf(Security::class, $app->getServiceLocator()->get('security'));
        $this->assertInstanceOf(UrlManager::class, $app->getServiceLocator()->get('urlManager'));
        $this->assertInstanceOf(EventManager::class, $app->getServiceLocator()->get('signal'));
    }

    public function testModules()
    {
        $app = new TestApplication([
            'basePath' => __DIR__ . '/app',
            'modules' => [
                'Foo' => [
                    'class' => FooBarModule::class,
                    'host' => 'domain.super'
                ],
                'Bar' => FooBarModule::class
            ]
        ]);
        $this->assertInstanceOf(FooBarModule::class, $app->getModule('Foo'));
        $this->assertEquals('domain.super', $app->getModule('Foo')->host);

        $this->assertInstanceOf(FooBarModule::class, $app->getModule('Bar'));
        $this->assertEquals('example.com', $app->getModule('Bar')->host);

        $this->assertEquals(__DIR__, Alias::get('Foo'));
        $this->assertEquals(__DIR__, Alias::get('Bar'));

        $this->assertEquals(['Foo', 'Bar'], array_keys($app->getModules()));
    }

    public function testAliases()
    {
        $app = new TestApplication([
            'basePath' => __DIR__ . '/app',
            'aliases' => [
                'foo' => __DIR__
            ]
        ]);
        $this->assertEquals(__DIR__, Alias::get('foo'));
    }
}
