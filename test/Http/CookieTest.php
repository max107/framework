<?php

namespace Mindy\Http;

class CookieTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers CHttpCookie::configure
     * @covers CHttpCookie::__construct
     */
    public function testConfigure()
    {
        //covers construct
        $cookie = new Cookie('name', 'value');
        $this->assertEquals('name', $cookie->name, 'Constructor failure. Name should have been set there');
        $this->assertEquals('value', $cookie->value, 'Constructor failure. Value should have been set there');
        $this->assertEquals('', $cookie->domain, 'Default value for HttpCookie::$domain has been touched');
        $this->assertEquals(0, $cookie->expire, 'Default value for HttpCookie::$expire has been touched');
        $this->assertEquals('/', $cookie->path, 'Default value for HttpCookie::$path has been touched');
        $this->assertFalse($cookie->secure, 'Default value for HttpCookie::$secure has been touched');
        $this->assertFalse($cookie->httpOnly, 'Default value for HttpCookie::$httpOnly has been touched');
        $options = array(
            'expire' => 123123,
            'httpOnly' => true,
        );
        // create cookie with options
        $cookie2 = new Cookie('name2', 'value2', $options);
        $this->assertEquals($options['expire'], $cookie2->expire, 'Configure inside the Constructor has been failed');
        $this->assertEquals($options['httpOnly'], $cookie2->httpOnly, 'Configure inside the Constructor has been failed');

        $cookie = new Cookie('name2', 'value2', $options);
        $this->assertEquals($options['expire'], $cookie->expire);
        $this->assertEquals($options['httpOnly'], $cookie->httpOnly);

        $cookie->value = 'someNewValue';
        $this->assertEquals('someNewValue', $cookie->value);
        //new configure should not override already set configuration
        $this->assertEquals($options['httpOnly'], $cookie->httpOnly);
    }

    /**
     * @covers HttpCookie::__toString
     */
    public function test__ToString()
    {
        $cookie = new Cookie('name', 'someValue');
        // Note on http://www.php.net/manual/en/language.oop5.magic.php#object.tostring
        ob_start();
        echo $cookie;
        $this->assertEquals(ob_get_clean(), $cookie->value);
        if (version_compare(PHP_VERSION, '5.2', '>=')) {
            $this->assertEquals($cookie->value, (string)$cookie);
        }
    }
}
