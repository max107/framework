<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 22:40
 */

namespace Mindy\Console\Tests;

use Mindy\Console\ConsoleCommandRunner;
use Mindy\Console\HelpCommand;

class ConsoleCommandRunnerTest extends \PHPUnit_Framework_TestCase
{
    public function getRunner()
    {
        return new ConsoleCommandRunner([
            'commands' => [
                'foo' => FooCommand::class
            ]
        ]);
    }

    public function testGetScriptName()
    {
        $runner = $this->getRunner();
        $this->assertNull($runner->getScriptName());
        $runner->run(['qwe.php', 'foo', 'sync']);
        $this->assertSame('qwe.php', $runner->getScriptName());
    }

    public function testGetCommand()
    {
        $runner = $this->getRunner();
        $runner->run(['qwe.php', 'foo', 'sync']);
    }

    public function testSetCommand()
    {

    }

    public function testFindCommands()
    {
        $runner = $this->getRunner();
        $commands = $runner->findCommands(__DIR__ . '/commands');
        $this->assertEquals(['find_me' => __DIR__ . '/commands/FindMeCommand.php'], $commands);

        $commands = $runner->findCommands('???');
        $this->assertEquals([], $commands);
    }

    public function testAddCommands()
    {
        $runner = $this->getRunner();
        $runner->addCommands(__DIR__ . '/commands', 'new');
        $this->assertEquals(['foo', 'new:find_me'], array_keys($runner->commands));
    }

    public function testGetClassesFromCode()
    {

    }

    public function testCreateCommand()
    {
        $runner = new ConsoleCommandRunner([
            'commands' => [
                'FOO' => FooCommand::class
            ]
        ]);
        $this->assertNull($runner->createCommand('unknown'));
        $this->assertInstanceOf(FooCommand::class, $runner->createCommand('foo'));
        $this->assertInstanceOf(HelpCommand::class, $runner->createCommand('help'));
    }

    public function testRun()
    {
        $runner = $this->getRunner();
        ob_start();
        $exitCode = $runner->run(['index.php', 'help']);
        ob_get_clean();
        $this->assertEquals(1, $exitCode);

        $exitCode = $runner->run(['index.php', 'foo', 'sync']);
        $this->assertEquals(0, $exitCode);

        ob_start();
        $exitCode = $runner->run(['index.php']);
        ob_get_clean();
        $this->assertEquals(1, $exitCode);

        ob_start();
        $exitCode = $runner->run(['index.php', 'qwe']);
        ob_get_clean();
        $this->assertEquals(1, $exitCode);
    }
}