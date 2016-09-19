<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 23:49
 */

namespace Mindy\Migration\Commands;

use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand as BaseDiffCommand;
use Symfony\Component\Console\Input\InputOption;

class DiffCommand extends BaseDiffCommand
{
    protected function configure()
    {
        parent::configure();
        $this->addOption('module', 'm', InputOption::VALUE_REQUIRED);
    }
}