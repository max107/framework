<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 19:09
 */

namespace Mindy\Tests\Session;

use Mindy\Session\Flash;

class FlashTest extends \PHPUnit_Framework_TestCase
{
    public function testFlash()
    {
        $flash = new Flash();
        $this->assertEquals(0, count($flash));
    }

    public function testAdd()
    {
        $flash = new Flash();
        $flash->success('success');
        $this->assertEquals([
            ['status' => 'success', 'message' => 'success']
        ], $flash->all());
        $this->assertEquals([], $flash->all());
    }
}