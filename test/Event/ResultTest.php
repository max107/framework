<?php

namespace Mindy\Test;

use Mindy\Event\Result;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testResult()
    {
        $origin = new \StdClass;
        $sender = get_class($origin);
        $signal = 'mock_signal';
        $value  = 'mock_value';
        
        $result = new Result($origin, $sender, $signal, $value);
        
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($result->origin, $origin);
        $this->assertSame($result->sender, $sender);
        $this->assertSame($result->signal, $signal);
        $this->assertSame($result->value, $value);
    }
}
