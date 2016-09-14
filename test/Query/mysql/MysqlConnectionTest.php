<?php

namespace Mindy\Tests\Query;

/**
 * @group db
 * @group pgsql
 */
class MysqlConnectionTest extends ConnectionTest
{
    protected $driverName = 'mysql';

    public function testConnection()
    {
        $this->getDb(true);
    }
}
