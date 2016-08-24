<?php

namespace Mindy\Helper\Tests;

use Mindy\Helper\Dumper;

/**
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 07/01/14.01.2014 13:50
 */


class DumperTest extends \PHPUnit_Framework_TestCase
{
    public function testDump()
    {
        $obj = new \StdClass();
        ob_start();
        Dumper::dump($obj);
        $this->assertEquals("stdClass#1\n(\n)", ob_get_contents());
        ob_end_clean();
    }
}
