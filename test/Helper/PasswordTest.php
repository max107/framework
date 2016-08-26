<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 16:52
 */

namespace Mindy\Helper\Tests;

use Exception;
use Mindy\Helper\Password;

class PasswordTest extends \PHPUnit_Framework_TestCase
{
    public function testPassword()
    {
        $hash = Password::hashPassword('123456');
        $this->assertEquals(60, mb_strlen($hash, '8bit'));
        $this->assertTrue(Password::verifyPassword('123456', $hash));

        $this->assertEquals(29, strlen(Password::generateSalt()));
        $this->assertEquals(29, strlen(Password::generateSalt(15)));

        $this->assertFalse(Password::verifyPassword('', ''));
        $this->assertFalse(Password::verifyPassword('123456', '@'));
        $this->assertFalse(Password::same('1', '22'));
    }

    public function testException()
    {
        $this->setExpectedException(Exception::class);
        $this->assertFalse(Password::generateSalt(1));
    }
}