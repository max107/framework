<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 14:11
 */

namespace Modules\Core\Commands;

use Mindy\Base\Mindy;
use Mindy\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ConsoleCommand
{
    public function configure()
    {
        $this
            ->setName('test')
            ->setHelp('Yo!')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'User Creator',
            '============',
            $input->getArgument('username'),
        ]);

        $models = Mindy::app()->getModule('Core')->getModels();
    }
}