<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:18
 */

namespace Mindy\Tests\Session;

use Mindy\Creator\Creator;
use Mindy\Session\Handler\SessionHandlerInterface;
use Mindy\Session\Handler\MemorySessionHandler;
use Mindy\Session\Handler\NativeSessionHandler;
use Mindy\Session\Session;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        ini_set('session.save_handler', 'files');
        ini_set('session.save_path', '/tmp/');
    }

    protected function sessionTesting(SessionHandlerInterface $handler)
    {
        $session = new Session([
            'handler' => $handler
        ]);

        $this->assertFalse($session->isStarted());
        $session->start();
        $this->assertTrue($session->isStarted());

        $this->assertTrue($session->set('foo', 'bar'));
        $this->assertEquals('bar', $session->get('foo'));
        $this->assertEquals('baz', $session->get('foo123', 'baz'));
        $this->assertEquals(1, count($session));

        $this->assertTrue($session->clear());
        $this->assertEquals(0, count($session));
        $session->close();
    }

    public function testMemorySession()
    {
        $handler = new MemorySessionHandler();
        $this->sessionTesting($handler);
    }

    /**
     * @runInSeparateProcess
     */
    public function testMemcachedSession()
    {
        if (!extension_loaded('php_memcached')) {
            $this->markTestSkipped('Failed to connect to memcached');
        }

        $serverString = "127.0.0.1:11211?persistent=1&weight=1&timeout=1&retry_interval=15";
        $handler = new NativeSessionHandler([
            'iniOptions' => [
                'save_handler' => 'memcached',
                'save_path' => $serverString
            ]
        ]);
        $this->sessionTesting($handler);
        $this->assertEquals(ini_get('session.save_path'), $serverString);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedisSession()
    {
        if (!extension_loaded('php_redis')) {
            $this->markTestSkipped('Failed to connect to redis');
        }

        $serverString = "tcp://127.0.0.1:6379";
        $handler = new NativeSessionHandler([
            'iniOptions' => [
                'save_handler' => 'redis',
                'save_path' => $serverString
            ]
        ]);
        $this->sessionTesting($handler);
        $this->assertEquals(ini_get('session.save_path'), $serverString);
    }

    /**
     * @runInSeparateProcess
     */
    public function testNativeSession()
    {
        $this->sessionTesting(new NativeSessionHandler());
    }

    public function testInitFromArray()
    {
        $config = [
            'class' => Session::class,
            'handler' => [
                'class' => MemorySessionHandler::class,
                'iniOptions' => [
                    'save_handler' => 'redis',
                    'save_path' => 'unknown'
                ]
            ]
        ];

        $session = Creator::createObject($config);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertInstanceOf(SessionHandlerInterface::class, $session->getHandler());
    }
}