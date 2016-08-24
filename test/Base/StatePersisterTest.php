<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 23:51
 */

namespace Mindy\Base\Tests;

use Mindy\Base\Traits\StatePersisterTrait;

class Example
{
    use StatePersisterTrait;

    public function getRuntimePath()
    {
        return __DIR__;
    }
}

class StatePersisterTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        unlink(__DIR__ . '/state.bin');
    }

    public function testSaveLoad()
    {
        @unlink(__DIR__ . '/state.bin');
        $state = new Example();
        $this->assertFalse($state->loadStorage());
        $state->saveStorage(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $state->loadStorage());
    }
}