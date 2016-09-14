<?php

namespace Mindy\Tests\Query;

use Mindy\QueryBuilder\Database\Pgsql\Adapter;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 05/02/15 17:06
 */
class PostgreSQLCommandTest extends CommandTest
{
    public $driverName = 'pgsql';

    public function getAdapter()
    {
        return new Adapter();
    }
}
