<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 14/02/15 18:24
 */

namespace Mindy\Router\Tests;

use Mindy\Auth\AuthProvider;
use Mindy\Auth\UserInterface;
use Mindy\Base\Mindy;
use Mindy\Http\Http;
use Mindy\Permissions\Rule;
use Mindy\Router\Dispatcher;
use Mindy\Router\Patterns;
use Mindy\Session\Adapter\MemorySessionHandler;
use Mindy\Session\Session;

class CustomDispatcher extends Dispatcher
{
    public function trailingSlashCallback($uri)
    {
        return 301;
    }
}

class PatternsTest extends \PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $callback = function () {
            return true;
        };

        $patterns = new Patterns([
            '/blog' => new Patterns([
                [
                    'route' => '/',
                    'name' => 'index',
                    'callback' => $callback
                ],
                [
                    'route' => '/view/{id:i}',
                    'name' => 'view',
                    'callback' => $callback
                ]
            ], 'blog'),
            '' => new Patterns([
                [
                    'route' => 'forum',
                    'name' => 'index',
                    'callback' => $callback
                ]
            ], 'forum'),
            '/page' => new Patterns([
                [
                    'route' => '/',
                    'name' => 'index',
                    'callback' => $callback
                ]
            ], 'page')
        ]);
        $c = $patterns->getRouteCollector();

        $this->assertEquals('/blog/', $c->reverse('blog:index'));
        $this->assertEquals('/blog/view/1', $c->reverse('blog:view', 1));
        $this->assertEquals('/blog/view/1', $c->reverse('blog:view', [1]));
        $this->assertEquals('/blog/view/1', $c->reverse('blog:view', ['id' => 1]));
        $this->assertEquals('/forum', $c->reverse('forum:index'));
        $this->assertEquals('/page/', $c->reverse('page:index'));

        $d = new CustomDispatcher($c);

        $this->assertNotNull($d->dispatch('GET', '/blog/'));
        $this->assertFalse($d->dispatch('GET', '/blog'));
        $this->assertTrue($d->dispatch('GET', '/blog/'));

        $this->assertFalse($d->dispatch('GET', '/page'));
        $this->assertTrue($d->dispatch('GET', '/page/'));
    }
}
