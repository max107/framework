<?php

namespace Mindy\Controller\Tests;

use Exception;
use Mindy\Controller\BaseController;

class Controller extends BaseController
{
    public function actionIndex()
    {
        return func_get_args();
    }

    public function actionView($name = 'foo')
    {
        return func_get_args();
    }

    protected function beforeAction($actionID, $params = [])
    {
        if ($actionID == 'beforeAfter') {
            echo 'before';
        }
    }

    protected function afterAction($actionID, $params = [], $out)
    {
        if ($actionID == 'beforeAfter') {
            echo 'after';
        }
    }

    public function actionBeforeAfter()
    {
        return '';
    }
}

class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testAction()
    {
        $c = new Controller();
        $out = $c->run('index');
        $this->assertEquals([], $out);

        $out = $c->run('index', ['name' => 1]);
        $this->assertEquals([], $out);

        $out = $c->run('view');
        $this->assertEquals(['foo'], $out);

        $out = $c->run('view', ['name' => 'bar']);
        $this->assertEquals(['bar'], $out);

        $out = $c->run('view', ['path' => 'bar']);
        $this->assertEquals(['foo'], $out);
    }

    public function testBeforeAfter()
    {
        $c = new Controller();
        
        ob_start();
        $c->run('BeforeAfter');
        $this->assertEquals('beforeafter', ob_get_clean());

        ob_start();
        $c->run('beforeAfter');
        $this->assertEquals('beforeafter', ob_get_clean());
    }

    public function testMissingAction()
    {
        $c = new Controller();
        $this->setExpectedException(Exception::class);
        $c->run('unknown');
    }
}