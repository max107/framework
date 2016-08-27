<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 18:40
 */

namespace Mindy\Http;

use GuzzleHttp\Psr7\UploadedFile;

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

    /**
     * @expectedException Exception
     */
    public function testGetSetException()
    {
        $http = new Http;
        $this->assertEquals('bar', $http->get->set('qwe', 'bar'));
    }

    /**
     * @expectedException Exception
     */
    public function testPostSetException()
    {
        $http = new Http;
        $this->assertEquals('bar', $http->post->set('qwe', 'bar'));
    }

    /**
     * @expectedException Exception
     */
    public function testCookiesSetException()
    {
        $http = new Http;
        $this->assertEquals('bar', $http->cookies->set('qwe', 'bar'));
    }

    /**
     * @expectedException Exception
     */
    public function testFilesSetException()
    {
        $http = new Http;
        $this->assertEquals('bar', $http->files->set('qwe', 'bar'));
    }
}