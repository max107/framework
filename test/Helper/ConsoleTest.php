<?php

namespace Mindy\Helper\Tests;

use Mindy\Helper\Console;
use PHPUnit_Framework_TestCase;

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
class ConsoleTest extends PHPUnit_Framework_TestCase
{
    public function testCli()
    {
        $this->assertTrue(Console::isCli());
    }
}
