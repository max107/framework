<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 23:23
 */

namespace Mindy\Migration\Commands;

use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand as BaseGenerateCommand;
use Symfony\Component\Console\Input\InputOption;

class GenerateCommand extends BaseGenerateCommand
{
    protected function configure()
    {
        parent::configure();
        $this->addOption('module', 'm', InputOption::VALUE_REQUIRED);
    }
}