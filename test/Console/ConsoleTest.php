<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 15:03
 */

namespace Mindy\Tests\Console;

use Mindy\Console\ConsoleApplication;
use Exception;
use Symfony\Component\Console\Tester\CommandTester;

require_once 'empty/EmptyCommand.php';
require_once 'data/FailCommand.php';
require_once 'data/SuccessCommand.php';

class ConsoleTest extends \PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $application = new ConsoleApplication();
        $commands = $application->findCommands(__DIR__ . DIRECTORY_SEPARATOR . 'data');
        $application->addCommands($commands);

        $commands = $application->all();
        $this->assertEquals(3, count($commands));

        $command = $application->find('success');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('SuccessCommand foo:bar', $output);
    }

    public function testEmpty()
    {
        $this->setExpectedException(Exception::class);
        $application = new ConsoleApplication();
        $application->findCommands(__DIR__ . DIRECTORY_SEPARATOR . 'empty');
    }
}