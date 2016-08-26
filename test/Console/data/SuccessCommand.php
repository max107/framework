<?php
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 15:05
 */
class SuccessCommand extends \Mindy\Console\ConsoleCommand
{
    public function configure()
    {
        $this->setName('success');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('SuccessCommand foo:bar');
    }
}