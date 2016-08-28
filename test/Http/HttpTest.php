<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 18:40
 */

namespace Mindy\Http;

use GuzzleHttp\Psr7\UploadedFile;
use Mindy\Http\Response\Response;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $_GET = ['foo' => 'bar'];
        $_POST = ['bar' => 'baz'];
        $_COOKIE = ['qwe' => 'ewq'];
        $_FILES = ['music' => [
            'tmp_name' => 'foo',
            'size' => 123,
            'error' => 0,
            'name' => 'foo',
            'type' => 'foo'
        ]];
        $http = new Http();
        $this->assertEquals('bar', $http->get->get('foo'));
        $this->assertEquals('bar', $http->get->get('qwe', 'bar'));
        $this->assertEquals('baz', $http->post->get('bar'));
        $file = $http->files->get('music');
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals(123, $file->getSize());
    }

    public function testCookie()
    {
        $response = new Response();
        $response = $response->withCookie([
            'name' => 'test',
            'value' => 'test'
        ]);
        $cookie = $response->getCookies()['test'];
        $this->assertEquals('test', $cookie->getName());
        $this->assertEquals('test', $cookie->getValue());
        $response = $response->withoutCookie('test');
        $this->assertEquals(1, count($response->getCookies()));
        $cookie = $response->getCookies()['test'];
        $this->assertEquals(0, $cookie->getExpires());
    }
}