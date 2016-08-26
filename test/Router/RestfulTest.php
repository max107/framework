<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 20:30
 */

namespace Mindy\Router\Tests;

use Mindy\Router\Dispatcher;
use Mindy\Router\RouteCollector;
use Mindy\Router\RouteParser;

class RestfulController
{
    public function getIndex($fistName = '?', $lastName = '?')
    {
        return $fistName . $lastName;
    }
}

class Test
{
    public function route()
    {
        return 'testRoute';
    }

    public function anyIndex()
    {
        return 'testRoute';
    }

    public function anyTest()
    {
        return 'testRoute';
    }

    public function getTest()
    {
        return 'testRoute';
    }

    public function postTest()
    {
        return 'testRoute';
    }

    public function putTest()
    {
        return 'testRoute';
    }

    public function deleteTest()
    {
        return 'testRoute';
    }

    public function headTest()
    {
        return 'testRoute';
    }

    public function optionsTest()
    {
        return 'testRoute';
    }

    public function getCamelCaseHyphenated()
    {
        return 'hyphenated';
    }

    public function getParameter($param)
    {
        return $param;
    }

    public function getParameterHyphenated($param)
    {
        return $param;
    }

    public function getParameterOptional($param = 'default')
    {
        return $param;
    }

    public function getParameterRequired($param)
    {
        return $param;
    }

    public function getParameterOptionalRequired($param, $param2 = 'default')
    {
        return $param . $param2;
    }
}

class RestfulTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router()
    {
        return new RouteCollector(new RouteParser);
    }

    private function dispatch($router, $method, $uri)
    {
        return (new Dispatcher($router))->dispatch($method, $uri);
    }

    public function testRestful()
    {
        $collector = new RouteCollector(new RouteParser());
        $collector->restful('/controller/', RestfulController::class);
        $collector->addRoute('GET', '/test/', [RestfulController::class => 'getIndex']);
        $collector->addRoute('GET', ['/user/{name:c}?', 'view_user'], function ($name = null) {
            return $name;
        });

        $dispatcher = new Dispatcher($collector);
        $this->assertEquals(false, $dispatcher->dispatch('GET', '/'));
        $this->assertEquals(false, $dispatcher->dispatch('GET', '/controller/'));
        $this->assertEquals('firstlast', $dispatcher->dispatch('GET', '/controller/index/first/last'));
        $this->assertEquals(false, $dispatcher->dispatch('GET', ''));
    }

    public function testRestfulControllerMethods()
    {

        $r = $this->router();

        $r->restful('/user', __NAMESPACE__ . '\\Test');

        $data = $r->getData();

//        $this->assertEquals($r->getValidMethods(), array_keys($data[0]['user/test']));
//        $this->assertEquals(array(Dispatcher::ANY), array_keys($data[0]['user/index']));
//        $this->assertEquals('hyphenated', $this->dispatch($r, Dispatcher::GET, 'user/camel-case-hyphenated'));
//        $this->assertEquals('joe', $this->dispatch($r, Dispatcher::GET, 'user/parameter/joe'));
//        $this->assertEquals('joe', $this->dispatch($r, Dispatcher::GET, 'user/parameter-hyphenated/joe'));
//        $this->assertEquals('joe', $this->dispatch($r, Dispatcher::GET, 'user/parameter-optional/joe'));

        $this->assertEquals('default', $this->dispatch($r, Dispatcher::GET, 'user/parameter-optional'));
        $this->assertEquals('joedefault', $this->dispatch($r, Dispatcher::GET, 'user/parameter-optional-required/joe'));

//        $this->assertEquals('joegreen', $this->dispatch($r, Dispatcher::GET, 'user/parameter-optional-required/joe/green'));
    }

    public function testRestfulOptionalRequiredControllerMethodThrows()
    {
        $r = $this->router();
        $r->restful('/user', __NAMESPACE__ . '\\Test');

        $this->assertFalse($this->dispatch($r, Dispatcher::GET, '/user/parameter-optional-required'));
    }

    public function testRestfulRequiredControllerMethodThrows()
    {
        $r = $this->router();
        $r->restful('/user', __NAMESPACE__ . '\\Test');

        $this->assertFalse($this->dispatch($r, Dispatcher::GET, '/user/parameter-required'));
    }

    public function testRestfulHyphenateControllerMethodThrows()
    {
        $r = $this->router();
        $r->restful('/user', __NAMESPACE__ . '\\Test');
        $this->assertFalse($this->dispatch($r, Dispatcher::GET, 'user/camelcasehyphenated'));
    }

    public function testRestfulMethods()
    {

        $r = $this->router();

        $methods = $r->getValidMethods();

        foreach ($methods as $method) {
            $r->$method('/user', 'callback');
        }

        $data = $r->getData();

        $this->assertEquals($methods, array_keys($data[0]['user']));
    }
}