<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:18
 */

namespace Mindy\Tests\Session;

use Mindy\Session\Adapter\SessionAdapterInterface;
use Mindy\Session\Adapter\MemcachedSessionAdapter;
use Mindy\Session\Adapter\MemorySessionAdapter;
use Mindy\Session\Adapter\NativeSessionAdapter;
use Mindy\Session\Adapter\RedisSessionAdapter;
use Mindy\Session\Session;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        ini_set('session.save_handler', 'files');
        ini_set('session.save_path', '/tmp/');
    }

    protected function sessionTesting(SessionAdapterInterface $handler)
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
        $handler = new MemorySessionAdapter();
        $this->sessionTesting($handler);
    }

    /**
     * @runInSeparateProcess
     */
    public function testMemcachedSession()
    {
        $handler = new MemcachedSessionAdapter();
        $this->sessionTesting($handler);
        $this->assertEquals(ini_get('session.save_path'), $handler->getServerString());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedisSession()
    {
        if (!extension_loaded('php_redis')) {
            $this->markTestSkipped('Failed to connect to redis');
        }

        $handler = new RedisSessionAdapter();
        $this->sessionTesting($handler);
        $this->assertEquals(ini_get('session.save_path'), $handler->getServerString());
    }

    /**
     * @runInSeparateProcess
     */
    public function testNativeSession()
    {
        $this->sessionTesting(new NativeSessionAdapter());
    }
}