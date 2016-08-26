<?php
/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/06/14.06.2014 18:49
 */

namespace Mindy\Console;

use Symfony\Component\Console\Command\Command;

abstract class ConsoleCommand extends Command
{
    public function setModuleId($id)
    {
        return $this->setName(sprintf("%s:%s", $id, $this->getName()));
    }
}
