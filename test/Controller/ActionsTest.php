<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/08/16
 * Time: 15:42
 */

namespace Mindy\Tests\Controller;

use Mindy\Controller\Action\Action;
use Mindy\Controller\Action\ClosureAction;
use Mindy\Controller\Action\InlineAction;
use Mindy\Controller\BaseController;
use Mindy\Exception\HttpException;

class DummyAction extends Action
{
    public function run()
    {
        return 'foo';
    }
}

class UsersExternalAction extends Action
{
    public function run(array $users = [1, 2, 3])
    {
        return implode(',', $users);
    }
}

class ExampleController extends BaseController
{
    public function actions()
    {
        return [
            'external' => ['class' => ExternalAction::class],
            'usersExternal' => ['class' => UsersExternalAction::class],
            'dummy' => ['class' => DummyAction::class]
        ];
    }

    public function actionIndex($name = 'foo')
    {
        return $name;
    }

    public function actionView()
    {
        return 'foo';
    }

    public function actionUsers(array $users = [1, 2, 3])
    {
        return implode(',', $users);
    }

    public function actionFalse($name)
    {
    }
}

class ExternalAction extends Action
{
    /**
     * @param string $name
     * @return mixed
     */
    public function run($name = 'foo')
    {
        return $name;
    }
}

class ActionsTest extends \PHPUnit_Framework_TestCase
{
    public function testClosure()
    {
        $action = new ClosureAction(function ($name = 'foo') {
            return $name;
        });
        $this->assertEquals('Closure', $action->getId());
        $this->assertEquals('foo', $action->runInternal());
        $this->assertEquals('bar', $action->runInternal(['name' => 'bar']));

        $action = new ClosureAction(function () {
            return 'foo';
        });
        $this->assertEquals('foo', $action->runInternal());
        $this->assertEquals('foo', $action->runInternal(['name' => 'bar']));

        $action = new ClosureAction(function (array $users = [1, 2, 3]) {
            return implode(',', $users);
        });
        $this->assertEquals('1,2,3', $action->runInternal());
        $this->assertEquals('3,2,1', $action->runInternal(['users' => [3, 2, 1]]));
    }

    public function testInline()
    {
        $c = new ExampleController;

        $action = new InlineAction($c, 'index');
        $this->assertEquals('foo', $action->runInternal());
        $this->assertEquals('bar', $action->runInternal(['name' => 'bar']));

        $action = new InlineAction($c, 'view');
        $this->assertEquals('foo', $action->runInternal());
        $this->assertEquals('foo', $action->runInternal(['name' => 'bar']));

        $action = new InlineAction($c, 'users');
        $this->assertEquals('1,2,3', $action->runInternal());
        $this->assertEquals('3,2,1', $action->runInternal(['users' => [3, 2, 1]]));
    }

    public function testExternal()
    {
        $controller = new ExampleController;

        $this->assertEquals('foo', $controller->run('external'));
        $this->assertEquals('bar', $controller->run('external', ['name' => 'bar']));

        $this->assertEquals('foo', $controller->run('dummy'));
        $this->assertEquals('foo', $controller->run('dummy', ['name' => 'bar']));

        $this->assertEquals('1,2,3', $controller->run('usersExternal'));
        $this->assertEquals('3,2,1', $controller->run('usersExternal', ['users' => [3, 2, 1]]));

        $this->assertEquals(DummyAction::class . ':run (dummy)', (new DummyAction($controller, 'dummy'))->getId());
    }

    public function testUnknown()
    {
        $controller = new ExampleController;
        $this->assertEquals(null, $controller->createAction('unknown'));
    }

    public function testException()
    {
        $controller = new ExampleController;
        $this->setExpectedException(HttpException::class);
        $controller->run('unknown');
    }

    public function testExceptionOutput()
    {
        $controller = new ExampleController;
        $this->setExpectedException(HttpException::class);
        $controller->run('false');
    }
}