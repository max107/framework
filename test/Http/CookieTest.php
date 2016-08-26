<?php

namespace Mindy\Http;

class CookieTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigure()
    {
        $cookie = new Cookie('name', 'value');
        $this->assertEquals('name=value; Path=/', (string)$cookie);
        $cookie = new Cookie('name', 'value', ['path' => '/qwe']);
        $this->assertEquals('name=value; Path=/qwe', (string)$cookie);
        $cookie = new Cookie('name', 'value', [
            'path' => '/qwe',
            'httpOnly' => true,
            'domain' => 'example.com',
            'maxAge' => 0
        ]);
        $this->assertEquals('name=value; Domain=example.com; Path=/qwe; HttpOnly', (string)$cookie);
    }
}
