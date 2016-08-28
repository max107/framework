<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 21/10/14.10.2014 13:55
 */

namespace Mindy\Tests\Validation;

use Mindy\Interfaces\Arrayable;
use Mindy\Validation\DateValidator;
use Mindy\Validation\EmailValidator;
use Mindy\Validation\FileValidator;
use Mindy\Validation\IpValidator;
use Mindy\Validation\JsonValidator;
use Mindy\Validation\MaxLengthValidator;
use Mindy\Validation\MinLengthValidator;
use Mindy\Validation\UrlValidator;

class ValidatorsTest extends \PHPUnit_Framework_TestCase
{
    public function testMaxLengthValidator()
    {
        $v = new MaxLengthValidator(2);
        $this->assertEquals(2, $v->maxLength);
        $this->assertTrue($v->validate(1));
        $this->assertEquals([], $v->getErrors());
        $v->clearErrors();
        $this->assertFalse($v->validate(123));
        $this->assertEquals(['Maximum length is 2'], $v->getErrors());

        $v->clearErrors();
        $obj = new \stdClass;
        $this->assertTrue(!is_numeric($obj) && !is_string($obj));
        $this->assertFalse($v->validate($obj));
        $this->assertEquals(['object is not a string'], $v->getErrors());
    }

    public function testMinLengthValidator()
    {
        $v = new MinLengthValidator(2);
        $this->assertEquals(2, $v->minLength);
        $this->assertFalse($v->validate(1));
        $this->assertEquals(['Minimal length is 2'], $v->getErrors());
        $v->clearErrors();
        $this->assertTrue($v->validate(123));
        $this->assertEquals([], $v->getErrors());

        $v->clearErrors();
        $obj = new \stdClass;
        $this->assertTrue(!is_numeric($obj) && !is_string($obj));
        $this->assertFalse($v->validate($obj));
        $this->assertEquals(['object is not a string'], $v->getErrors());
    }

    public function testEmailValidator()
    {
        $v = new EmailValidator();
        $this->assertFalse($v->validate('admin@admin'));
        $this->assertEquals(['Is not a valid email address'], $v->getErrors());
        $v->clearErrors();

        $this->assertTrue($v->validate('admin@admin.com'));
        $this->assertEquals([], $v->getErrors());
        $v->clearErrors();

        $this->assertFalse($v->validate(''));
        $this->assertEquals(['Is not a valid email address'], $v->getErrors());
        $v->clearErrors();

        $this->assertFalse($v->validate(new \stdClass));
        $this->assertEquals(['Is not a valid email address'], $v->getErrors());
        $v->clearErrors();

        $v->checkDNS = true;
        $this->assertFalse($v->validate('qwe@foo.qwe'));
        $this->assertEquals(['Is not a valid email address'], $v->getErrors());
        $v->clearErrors();
    }

//    /**
//     * @expectedException \Mindy\Exception\InvalidConfigException
//     */
//    public function testEmailValidatorException()
//    {
//        $v = new EmailValidator();
//        $v->checkDNS = false;
//        $v->enableIDN = true;
//        $this->assertFalse($v->validate('qwe@foo.1'));
//        $this->assertEquals(['Is not a valid email address'], $v->getErrors());
//    }

    public function testUrlValidator()
    {
        $v = new UrlValidator();
        $this->assertTrue($v->validate('http://studio107.ru'));
        $v->clearErrors();

        $this->assertTrue($v->validate('ftp://studio107.ru'));
        $v->clearErrors();

        $this->assertFalse($v->validate('/foo/bar.html'));
        $v->clearErrors();

        $this->assertFalse($v->validate('qwe'));
        $v->clearErrors();

        $this->assertFalse($v->validate('studio107.ru'));
        $v->clearErrors();

        $this->assertFalse($v->validate(new \stdClass));
        $v->clearErrors();

        $v->pattern = '/^(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';
        $this->assertTrue($v->validate('studio107.ru'));
        $v->clearErrors();
    }

//    /**
//     * @expectedException \Mindy\Exception\InvalidConfigException
//     */
//    public function testUrlValidatorException()
//    {
//        $v = new UrlValidator();
//        $v->enableIDN = true;
//        $this->assertTrue($v->validate('studio107.ru'));
//    }

    public function testDateValidator()
    {
        date_default_timezone_set('UTC');

        $v = new DateValidator('Y-m-d');
        $this->assertTrue($v->validate('2012-10-11'));
        $v->clearErrors();

        $v = new DateValidator();
        $this->assertFalse($v->validate('2012-11-10 00:00:00'));
        $v->clearErrors();

        $this->assertTrue($v->validate('2012-10-11'));
        $v->clearErrors();

        $this->assertFalse($v->validate('2012-11-10 00:00:00'));
        $v->clearErrors();

        $this->assertFalse($v->validate(new \stdClass));
        $v->clearErrors();
    }

    public function testJsonValidator()
    {
        $v = new JsonValidator();

        $this->assertTrue($v->validate([]));
        $v->clearErrors();

        $this->assertTrue($v->validate(null));
        $v->clearErrors();

        $this->assertFalse($v->validate(new \stdClass));
        $this->assertEquals(['Not json serialize object: object'], $v->getErrors());
        $v->clearErrors();

        $obj = new Arr;
        $obj->data = [1, 2, 3];
        $this->assertTrue($v->validate($obj));
        $v->clearErrors();
    }

    public function testFileValidator()
    {
        $v = new FileValidator(false, ['txt']);

        $this->assertTrue($v->validate(['name' => 'test.txt', 'error' => UPLOAD_ERR_OK, 'size' => 1]));
        $v->clearErrors();

        $this->assertFalse($v->validate(['name' => 'test.mp3', 'error' => UPLOAD_ERR_OK, 'size' => 1]));
        $this->assertEquals(['Is not a valid file type mp3. Types allowed: txt'], $v->getErrors());
        $v->clearErrors();
    }

    public function testIpValidator()
    {
        $v = new IpValidator();
        $this->assertTrue($v->validate('127.0.0.1'));
        $this->assertTrue($v->validate('8.8.8.8'));
        $this->assertFalse($v->validate('8.8.8'));
        $this->assertEquals(['Is not a valid IP address.'], $v->getErrors());
    }
}

class Arr implements Arrayable
{
    public $data = [];

    public function toArray()
    {
        return $this->data;
    }
}
