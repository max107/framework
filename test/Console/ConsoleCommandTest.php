<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 22:44
 */

namespace Mindy\Console\Tests;

use Mindy\Console\ConsoleCommand;
use Mindy\Console\ConsoleCommandRunner;

class ExampleCommand extends ConsoleCommand
{
    public function actionIndex($path, $name = null)
    {
        return [$path, $name];
    }
}

class FooCommand extends ConsoleCommand
{
    public function actionIndex($path, $name = null)
    {

    }

    public function actionSync()
    {

    }
}

class EmptyCommand extends ConsoleCommand
{

}

class DefaultArrayCommand extends ConsoleCommand
{
    public function actionSync($params = [1, 2, 3])
    {

    }
}

class ConsoleCommandTest extends \PHPUnit_Framework_TestCase
{
    public function getRunner()
    {
        return new ConsoleCommandRunner();
    }

    public function testRun()
    {
        $cmd = new ExampleCommand('example', $this->getRunner());
        $out = $cmd->run(['index', '--path=example']);
        $this->assertEquals(['example', null], $out);

        $this->setExpectedException(\Exception::class);
        $out = $cmd->run(['--path=example']);
        $this->assertEquals(['example', null], $out);
    }

    public function testGetHelp()
    {
        $cmd = new ExampleCommand('example', $this->getRunner());
        $this->assertEquals('Usage:  example index --path=value [--name=]
', $cmd->getHelp());

        $cmd = new FooCommand('foo', $this->getRunner());
        $this->assertEquals('Usage:  foo <action>
Actions:
    index --path=value [--name=]
    sync
', $cmd->getHelp());

        $cmd = new EmptyCommand('empty', $this->getRunner());
        $this->assertEquals('Usage:  empty
', $cmd->getHelp());
    }

    public function testGetOptionsHelp()
    {
        $cmd = new ExampleCommand('example', $this->getRunner());
        $this->assertEquals(['index --path=value [--name=]'], $cmd->getOptionHelp());

        $cmd = new EmptyCommand('empty', $this->getRunner());
        $this->assertEquals([], $cmd->getOptionHelp());

        $cmd = new DefaultArrayCommand('default_array', $this->getRunner());
        $this->assertEquals(['sync [--params=[0 => 1, 1 => 2, 2 => 3]]'], $cmd->getOptionHelp());
    }
}