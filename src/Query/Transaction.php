<?php

namespace Mindy\Query;

/**
 * Transaction represents a DB transaction.
 *
 * It is usually created by calling [[Connection::beginTransaction()]].
 *
 * The following code is a typical example of using transactions (note that some
 * DBMS may not support transactions):
 *
 * ~~~
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     //.... other SQL executions
 *     $transaction->commit();
 * } catch(Exception $e) {
 *     $transaction->rollback();
 * }
 * ~~~
 *
 * @property boolean $isActive Whether this transaction is active. Only an active transaction can [[commit()]]
 * or [[rollback()]]. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 * @package Mindy\Query
 */
class Transaction
{
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const READ_UNCOMMITTED = 'READ UNCOMMITTED';
    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const READ_COMMITTED = 'READ COMMITTED';
    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const REPEATABLE_READ = 'REPEATABLE READ';
    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const SERIALIZABLE = 'SERIALIZABLE';
    /**
     * @var Connection the database connection that this transaction is associated with.
     */
    public $db;
    /**
     * @var integer the nesting level of the transaction. 0 means the outermost level.
     */
    private $_level = 0;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    protected function getLogger()
    {
        static $logger;
        if ($logger === null) {
            if (class_exists('\Mindy\Base\Mindy') && \Mindy\Base\Mindy::app()) {
                $logger = \Mindy\Base\Mindy::app()->logger;
            } else {
                $logger = new DummyObject;
            }
        }
        return $logger;
    }

    /**
     * Returns a value indicating whether this transaction is active.
     * @return boolean whether this transaction is active. Only an active transaction
     * can [[commit()]] or [[rollBack()]].
     */
    public function getIsActive()
    {
        return $this->_level > 0 && $this->db && $this->db->isActive;
    }

    /**
     * Begins a transaction.
     * @param string|null $isolationLevel The [isolation level][] to use for this transaction.
     * This can be one of [[READ_UNCOMMITTED]], [[READ_COMMITTED]], [[REPEATABLE_READ]] and [[SERIALIZABLE]] but
     * also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * If not specified (`null`) the isolation level will not be set explicitly and the DBMS default will be used.
     *
     * > Note: This setting does not work for PostgreSQL, where setting the isolation level before the transaction
     * has no effect. You have to call [[setIsolationLevel()]] in this case after the transaction has started.
     *
     * > Note: Some DBMS allow setting of the isolation level only for the whole connection so subsequent transactions
     * may get the same isolation level even if you did not specify any. When using this feature
     * you may need to set the isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * [isolation level]: http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     * @throws InvalidConfigException if [[db]] is `null`.
     */
    public function begin($isolationLevel = null)
    {
        $this->db->open();
        if ($this->_level == 0) {
            if ($isolationLevel !== null) {
                $this->db->getSchema()->setTransactionIsolationLevel($isolationLevel);
            }
            $this->getLogger()->debug('Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : ''), ['method' => __METHOD__]);
            $this->db->trigger(Connection::EVENT_BEGIN_TRANSACTION);
            $this->db->pdo->beginTransaction();
            $this->_level = 1;
            return;
        }
        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            $this->getLogger()->debug('Set savepoint ' . $this->_level, ['method' => __METHOD__]);
            $schema->createSavepoint('LEVEL' . $this->_level);
        } else {
            $this->getLogger()->debug('Transaction not started: nested transaction not supported', ['method' => __METHOD__]);
        }
        $this->_level++;
    }

    /**
     * Commits a transaction.
     * @throws Exception if the transaction is not active
     */
    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }
        $this->_level--;
        if ($this->_level == 0) {
            $this->getLogger()->debug('Commit transaction', ['method' => __METHOD__]);
            $this->db->pdo->commit();
            $this->db->trigger(Connection::EVENT_COMMIT_TRANSACTION);
            return;
        }
        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            $this->getLogger()->debug('Release savepoint ' . $this->_level, ['method' => __METHOD__]);
            $schema->releaseSavepoint('LEVEL' . $this->_level);
        } else {
            $this->getLogger()->debug('Transaction not committed: nested transaction not supported', ['method' => __METHOD__]);
        }
    }

    /**
     * Rolls back a transaction.
     * @throws Exception if the transaction is not active
     */
    public function rollBack()
    {
        if (!$this->getIsActive()) {
            // do nothing if transaction is not active: this could be the transaction is committed
            // but the event handler to "commitTransaction" throw an exception
            return;
        }
        $this->_level--;
        if ($this->_level == 0) {
            $this->getLogger()->debug('Roll back transaction', ['method' => __METHOD__]);
            $this->db->pdo->rollBack();
            $this->db->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);
            return;
        }
        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            $this->getLogger()->debug('Roll back to savepoint ' . $this->_level, ['method' => __METHOD__]);
            $schema->rollBackSavepoint('LEVEL' . $this->_level);
        } else {
            $this->getLogger()->debug('Transaction not rolled back: nested transaction not supported', ['method' => __METHOD__]);
            // throw an exception to fail the outer transaction
            throw new Exception('Roll back failed: nested transaction not supported.');
        }
    }

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However this is not supported by all DBMS so you might rather specify the isolation level directly
     * when calling [[begin()]].
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of [[READ_UNCOMMITTED]], [[READ_COMMITTED]], [[REPEATABLE_READ]] and [[SERIALIZABLE]] but
     * also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * @throws Exception if the transaction is not active
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setIsolationLevel($level)
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }
        $this->getLogger()->debug('Setting transaction isolation level to ' . $level, ['method' => __METHOD__]);
        $this->db->getSchema()->setTransactionIsolationLevel($level);
    }
}
