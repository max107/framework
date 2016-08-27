<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:18
 */

namespace Mindy\Tests\Session;

use Mindy\Session\Adapter\MemcachedSessionAdapter;
use Mindy\Session\Adapter\MemorySessionAdapter;
use Mindy\Session\Adapter\NativeSessionAdapter;
use Mindy\Session\Adapter\RedisSessionAdapter;
use Mindy\Session\Session;
use Mindy\Session\Adapter\SessionAdapterInterface;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected function sessionTesting(SessionAdapterInterface $handler)
    {
        $session = new Session([
            'handler' => $handler
        ]);

        $this->assertFalse($session->isStarted());
        $this->assertFalse($session->isClosed());
        $session->start();
        $this->assertTrue($session->isStarted());
        $this->assertFalse($session->isClosed());

        $this->assertTrue($session->set('foo', 'bar'));
        $this->assertEquals('bar', $session->get('foo'));
        $this->assertEquals('baz', $session->get('foo123', 'baz'));
        $this->assertEquals(1, count($session));

        $this->assertTrue($session->clear());
        $this->assertEquals(0, count($session));
    }

    public function testMemorySession()
    {
        $this->sessionTesting(new MemorySessionAdapter());
    }

    /**
     * @runInSeparateProcess
     */
    public function testMemcachedSession()
    {
        $this->sessionTesting(new MemcachedSessionAdapter());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedisSession()
    {
        $this->sessionTesting(new RedisSessionAdapter());
    }

    /**
     * @runInSeparateProcess
     */
    public function testNativeSession()
    {
        $this->sessionTesting(new NativeSessionAdapter());
    }
}