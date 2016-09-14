<?php

namespace Mindy\Tests\Query;

use Mindy\Query\Transaction;

/**
 * @group db
 * @group pgsql
 */
class PostgreSQLConnectionTest extends ConnectionTest
{
    protected $driverName = 'pgsql';

    public function testConnection()
    {
        $this->getDb(true);
    }

    public function testTransactionIsolation()
    {
        $connection = $this->getDb(true);
        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_UNCOMMITTED);
        $transaction->commit();
        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::READ_COMMITTED);
        $transaction->commit();
        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::REPEATABLE_READ);
        $transaction->commit();
        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE);
        $transaction->commit();
        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE . ' READ ONLY DEFERRABLE');
        $transaction->commit();
    }
}
