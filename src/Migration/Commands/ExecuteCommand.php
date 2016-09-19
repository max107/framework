<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 23:49
 */

namespace Mindy\Migration\Commands;

use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand as BaseExecuteCommand;
use Symfony\Component\Console\Input\InputOption;

class ExecuteCommand extends BaseExecuteCommand
{
    protected function configure()
    {
        parent::configure();
        $this->addOption('module', 'm', InputOption::VALUE_REQUIRED);
    }
}