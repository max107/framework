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

    public function flashProvider()
    {
        return [
            ['success'],
            ['info'],
            ['warning'],
            ['error']
        ];
    }

    /**
     * @dataProvider flashProvider
     */
    public function testStatuses($status)
    {
        $flash = new Flash();
        $flash->$status($status);
        $this->assertEquals([
            ['status' => $status, 'message' => $status]
        ], $flash->all());
        $this->assertEquals([], $flash->all());
    }

    public function testIterator()
    {
        $flash = new Flash;
        $flash->success('1');
        $flash->success('2');
        $flash->success('3');
        foreach ($flash as $msg) {

        }
        $this->assertEquals(0, $flash->count());
    }
}