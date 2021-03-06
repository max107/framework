<?php

namespace Mindy\Test;

use Mindy\Event\EventManager;

class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandlerAndGetHandlers()
    {
        $signal = new EventManager([
            'events' => [
                // no position
                [
                    '\UnexpectedValueException',
                    'mock_signal',
                    ['MockAlpha', 'method'],
                ],
                // early position
                [
                    '\StdClass',
                    'mock_signal',
                    ['MockBeta', 'method'],
                    4000
                ],
                // late position
                [
                    '\Exception',
                    'mock_signal',
                    ['MockGamma', 'method'],
                    6000
                ],
                // unused
                [
                    '\StdClass',
                    'unused_signal',
                    ['MockGamma', 'method'],
                ],
            ]
        ]);

        // get all handlers (unsorted)
        $handlers = $signal->getHandlers();
        $this->assertTrue(count($handlers) == 2);
        $this->assertTrue(isset($handlers['mock_signal']));
        $this->assertTrue(isset($handlers['unused_signal']));

        // now get them
        $handlers = $signal->getHandlers('mock_signal');

        // should be three position groups, in this order
        $expect = [4000, 5000, 6000];
        $actual = array_keys($handlers);
        $this->assertSame($expect, $actual);

        // make sure the handlers are in the right groups
        $this->assertSame('\StdClass', $handlers[4000][0]->sender);
        $this->assertSame('\UnexpectedValueException', $handlers[5000][0]->sender);
        $this->assertSame('\Exception', $handlers[6000][0]->sender);
    }

    public function testSend()
    {
        $signal = new EventManager([
            'events' => [
                // late position
                [
                    '\Unexpected',
                    'mock_signal',
                    function ($foo) {
                        return "$foo-unexpected";
                    },
                    6000
                ],
                // no position
                [
                    '\StdClass',
                    'mock_signal',
                    function ($foo) {
                        return "$foo-stdclass-mid";
                    },
                    5000
                ],
                // early position
                [
                    '\StdClass',
                    'mock_signal',
                    function ($foo) {
                        return "$foo-stdclass-early";
                    },
                    4000
                ],
                // unused
                [
                    '\StdClass',
                    'unused_signal',
                    ['MockGamma', 'method'],
                ],
            ]
        ]);

        // send a signal that should match two handlers
        $origin = new \StdClass;
        $signal->send($origin, 'mock_signal', ['hello']);
        $results1 = $signal->getResults();
        $this->assertEquals(2, count($results1));
        $this->assertSame('hello-stdclass-early', $results1[0]->value);
        $this->assertSame('hello-stdclass-mid', $results1[1]->value);

        // add a handler that stops processing
        $signal->handler(
            '\StdClass',
            'mock_signal',
            function ($foo) {
                return EventManager::STOP;
            },
            4500 // just before the mid group
        );

        $signal->send($origin, 'mock_signal', ['hello']);
        $results2 = $signal->getResults();
        $this->assertNotSame($results1, $results2);
        $this->assertEquals(2, count($results2));
        $this->assertSame('hello-stdclass-early', $results2[0]->value);
        $this->assertSame(EventManager::STOP, $results2[1]->value);
    }
}
