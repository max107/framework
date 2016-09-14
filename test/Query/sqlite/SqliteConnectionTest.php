<?php

namespace Mindy\Tests\Query;

use Mindy\Helper\Alias;
use Mindy\Helper\Creator;
use Mindy\Query\Connection;
use Mindy\Query\Transaction;

/**
 * @group db
 * @group sqlite
 */
class SqliteConnectionTest extends ConnectionTest
{
    protected $driverName = 'sqlite';

    public function testConstruct()
    {
        $connection = $this->getDb(false);
        $params = $this->config;
        $this->assertEquals($params['dsn'], $connection->dsn);
    }

    public function testTransactionIsolation()
    {
        $connection = $this->getDb(true);
        $transaction = $connection->beginTransaction(Transaction::READ_UNCOMMITTED);
        $transaction->rollBack();
        $transaction = $connection->beginTransaction(Transaction::SERIALIZABLE);
        $transaction->rollBack();
    }
}
