<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 09:44
 */

namespace Mindy\Router\Tests;

use Mindy\Router\Dispatcher;
use Mindy\Router\RouteCollector;
use Mindy\Router\RouteParser;

class RouteGroupTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupConfig()
    {
        $collector = new RouteCollector(new RouteParser());
        $collector->groupConfig('/blog//', function () {
            return [
                [
                    'route' => 'view/{id:i}',
                    'name' => 'blog:view',
                    'handler' => function ($id) {
                        return 'foo';
                    }
                ]
            ];
        });
        $dispatcher = new Dispatcher($collector);
        $this->assertEquals('foo', $dispatcher->dispatch('GET', '/blog/view/1'));
        $this->assertEquals('/blog/view/1', $dispatcher->reverse('blog:view', ['id' => 1]));
    }

    public function testGroup()
    {
        $collector = new RouteCollector(new RouteParser());
        $collector->group('/blog//', function (RouteCollector $groupCollector) {
            $groupCollector->get(['view/{id:i}', 'blog:view'], function ($id) {
                return 'foo';
            });
        });
        $dispatcher = new Dispatcher($collector);
        $this->assertEquals('foo', $dispatcher->dispatch('GET', '/blog/view/1'));
        $this->assertEquals('/blog/view/1', $dispatcher->reverse('blog:view', ['id' => 1]));
    }
}