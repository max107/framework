<?php

namespace Mindy\Query;

use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Query\Exception\Exception;

/**
 * Command represents a SQL statement to be executed against a database.
 *
 * A command object is usually created by calling [[Connection::createCommand()]].
 * The SQL statement it represents can be set via the [[sql]] property.
 *
 * To execute a non-query SQL (such as INSERT, DELETE, UPDATE), call [[execute()]].
 * To execute a SQL statement that returns result data set (such as SELECT),
 * use [[queryAll()]], [[queryOne()]], [[queryColumn()]], [[queryScalar()]], or [[query()]].
 * For example,
 *
 * ~~~
 * $users = $connection->createCommand('SELECT * FROM tbl_user')->queryAll();
 * ~~~
 *
 * Command supports SQL statement preparation and parameter binding.
 * Call [[bindValue()]] to bind a value to a SQL parameter;
 * Call [[bindParam()]] to bind a PHP variable to a SQL parameter.
 * When binding a parameter, the SQL statement is automatically prepared.
 * You may also call [[prepare()]] explicitly to prepare a SQL statement.
 *
 * Command also supports building SQL statements by providing methods such as [[insert()]],
 * [[update()]], etc. For example,
 *
 * ~~~
 * $connection->createCommand($sql)->execute();
 * ~~~
 *
 * To build SELECT SQL statements, please use [[QueryBuilder]] instead.
 *
 * @property string $sql The SQL statement to be executed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 * @package Mindy\Query
 */
class Command
{
    use Accessors, Configurator;

    /**
     * @var Connection the DB connection that this command is associated with
     */
    public $db;
    /**
     * @var array the parameters (name => value) that are bound to the current PDO statement.
     * This property is maintained by methods such as [[bindValue()]]. It is mainly provided for logging purpose
     * and is used to generate [[rawSql]]. Do not modify it directly.
     */
    public $params = [];
    /**
     * @var \PDOStatement the PDOStatement object that this command is associated with
     */
    public $pdoStatement;
    /**
     * @var integer the default fetch mode for this command.
     * @see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php
     */
    public $fetchMode = PDO::FETCH_ASSOC;
    /**
     * @var integer the default number of seconds that query results can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire. And use a negative number to indicate
     * query cache should not be used.
     * @see cache()
     */
    public $queryCacheDuration;
    /**
     * @var \Mindy\Cache\Dependency the dependency to be associated with the cached query result for this command
     * @see cache()
     */
    public $queryCacheDependency;
    /**
     * @var
     */
    protected $sql;
    /**
     * @var array pending parameters to be bound to the current PDO statement.
     */
    private $_pendingParams = [];
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Enables query cache for this command.
     * @param integer $duration the number of seconds that query result of this command can remain valid in the cache.
     * If this is not set, the value of [[Connection::queryCacheDuration]] will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param \Mindy\Cache\Dependency $dependency the cache dependency associated with the cached query result.
     * @return static the command object itself
     */
    public function cache($duration = null, $dependency = null)
    {
        $this->queryCacheDuration = $duration === null ? $this->db->queryCacheDuration : $duration;
        $this->queryCacheDependency = $dependency;
        return $this;
    }

    /**
     * Disables query cache for this command.
     * @return static the command object itself
     */
    public function noCache()
    {
        $this->queryCacheDuration = -1;
        return $this;
    }

    /**
     * Prepares the SQL statement to be executed.
     * For complex SQL statement that is to be executed multiple times,
     * this may improve performance.
     * For SQL statement with binding parameters, this method is invoked
     * automatically.
     * @param boolean $forRead whether this method is called for a read query. If null, it means
     * the SQL statement should be used to determine whether it is for read or write.
     * @throws Exception if there is any DB error
     */
    public function prepare()
    {
        if ($this->pdoStatement) {
            $this->bindPendingParams();
            return;
        }
        $sql = $this->getSql();
        $pdo = $this->db->getPdo();
        try {
            $this->pdoStatement = $pdo->prepare($sql);
            $this->bindPendingParams();
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
            $errorInfo = $e instanceof \PDOException ? $e->errorInfo : null;
            throw new Exception($message, $errorInfo, (int) $e->getCode(), $e);
        }
    }

    /**
     * Binds pending parameters that were registered via [[bindValue()]] and [[bindValues()]].
     * Note that this method requires an active [[pdoStatement]].
     */
    protected function bindPendingParams()
    {
        foreach ($this->_pendingParams as $name => $value) {
            $this->pdoStatement->bindValue($name, $value[0], $value[1]);
        }
        $this->_pendingParams = [];
    }

    /**
     * Binds a value to a parameter.
     * @param string|integer $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @return static the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value, $dataType = null)
    {
        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }
        $this->_pendingParams[$name] = [$value, $dataType];
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Binds a list of values to the corresponding parameters.
     * This is similar to [[bindValue()]] except that it binds multiple values at a time.
     * Note that the SQL data type of each value is determined by its PHP type.
     * @param array $values the values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and array values the corresponding parameter values,
     * e.g. `[':name' => 'John', ':age' => 25]`. By default, the PDO type of each value is determined
     * by its PHP type. You may explicitly specify the PDO type by using an array: `[value, type]`,
     * e.g. `[':name' => 'John', ':profile' => [$profile, \PDO::PARAM_LOB]]`.
     * @return static the current command being executed
     */
    public function bindValues($values)
    {
        if (empty($values)) {
            return $this;
        }
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
                $this->params[$name] = $value[0];
            } else {
                $type = $this->db->getSchema()->getPdoType($value);
                $this->_pendingParams[$name] = [$value, $type];
                $this->params[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * Cancels the execution of the SQL statement.
     * This method mainly sets [[pdoStatement]] to be null.
     */
    public function cancel()
    {
        $this->pdoStatement = null;
    }

    /**
     * Executes the SQL statement and returns query result.
     * This method is for executing a SQL query that returns result set, such as `SELECT`.
     * @return DataReader the reader object for fetching the query result
     * @throws Exception execution failed
     */
    public function query()
    {
        return $this->queryInternal('');
    }

    /**
     * Executes the SQL statement and returns ALL rows at once.
     * @param integer $fetchMode the result fetch mode. Please refer to [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php)
     * for valid fetch modes. If this parameter is null, the value set in [[fetchMode]] will be used.
     * @return array all rows of the query result. Each array element is an array representing a row of data.
     * An empty array is returned if the query results in nothing.
     * @throws Exception execution failed
     */
    public function queryAll($fetchMode = null)
    {
        return $this->queryInternal('fetchAll', $fetchMode);
    }

    /**
     * Executes the SQL statement and returns the first row of the result.
     * This method is best used when only the first row of result is needed for a query.
     * @param integer $fetchMode the result fetch mode. Please refer to [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php)
     * for valid fetch modes. If this parameter is null, the value set in [[fetchMode]] will be used.
     * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     * @throws Exception execution failed
     */
    public function queryOne($fetchMode = null)
    {
        return $this->queryInternal('fetch', $fetchMode);
    }

    /**
     * Executes the SQL statement and returns the value of the first column in the first row of data.
     * This method is best used when only a single value is needed for a query.
     * @return string|null|boolean the value of the first column in the first row of the query result.
     * False is returned if there is no value.
     * @throws Exception execution failed
     */
    public function queryScalar()
    {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        } else {
            return $result;
        }
    }

    /**
     * Executes the SQL statement and returns the first column of the result.
     * This method is best used when only the first column of result (i.e. the first element in each row)
     * is needed for a query.
     * @return array the first column of the query result. Empty array is returned if the query results in nothing.
     * @throws Exception execution failed
     */
    public function queryColumn()
    {
        return $this->queryInternal('fetchAll', \PDO::FETCH_COLUMN);
    }

    /**
     * Executes the SQL statement.
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     * @param bool $returnStatement
     * @return int number of rows affected by the execution.
     * @throws Exception execution failed
     * @throws \Exception
     * @throws \Mindy\Exception\NotSupportedException
     */
    public function execute($returnStatement = false)
    {
        $sql = $this->getSql();
        $this->getLogger()->debug($sql, ['method' => __METHOD__]);
        if ($sql == '') {
            return 0;
        }
        $this->prepare();
        $token = $sql;
        try {
            $this->getLogger()->debug('beginProfile: ' . $token, ['method' => __METHOD__]);
            $n = $this->pdoStatement->execute();
            if ($returnStatement === false) {
                $n = $this->pdoStatement->rowCount();
            }
            $this->getLogger()->debug('endProfile: ' . $token, ['method' => __METHOD__]);
            return $n;
        } catch (\Exception $e) {
            $this->getLogger()->debug('endProfile: ' . $token, ['method' => __METHOD__]);
            throw $this->db->getSchema()->convertException($e, $sql);
        }
    }

    /**
     * Binds a parameter to the SQL statement to be executed.
     * @param string|integer $name parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value Name of the PHP variable to bind to the SQL statement parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @param integer $length length of the data type
     * @param mixed $driverOptions the driver-specific options
     * @return static the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam($name, &$value, $dataType = null, $length = null, $driverOptions = null)
    {
        $this->prepare();
        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }
        if ($length === null) {
            $this->pdoStatement->bindParam($name, $value, $dataType);
        } elseif ($driverOptions === null) {
            $this->pdoStatement->bindParam($name, $value, $dataType, $length);
        } else {
            $this->pdoStatement->bindParam($name, $value, $dataType, $length, $driverOptions);
        }
        $this->params[$name] =& $value;
        return $this;
    }

    /**
     * Performs the actual DB query of a SQL statement.
     * @param string $method method of PDOStatement to be called
     * @param integer $fetchMode the result fetch mode. Please refer to [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php)
     * for valid fetch modes. If this parameter is null, the value set in [[fetchMode]] will be used.
     * @return mixed the method execution result
     * @throws Exception if the query causes any problem
     * @since 2.0.1 this method is protected (was private before).
     */
    protected function queryInternal($method, $fetchMode = null)
    {
        $sql = $this->getSql();
        $this->getLogger()->debug($sql, ['method' => __METHOD__]);
        if ($method !== '') {
            $info = $this->db->getQueryCacheInfo($this->queryCacheDuration, $this->queryCacheDependency);
            if (is_array($info)) {
                /* @var $cache \Mindy\Cache\Cache */
                $cache = $info[0];
                $cacheKey = [
                    __CLASS__,
                    $method,
                    $fetchMode,
                    $this->db->dsn,
                    $this->db->username,
                    $sql,
                ];
                if (($result = $cache->get($cacheKey)) !== false) {
                    $this->getLogger()->debug('Query result served from cache', ['method' => __METHOD__]);
                    return $result;
                }
            }
        }
        $this->prepare();
        $token = $sql;
        try {
            $this->getLogger()->debug('beginProfile: ' . $token, ['method' => __METHOD__]);
            $this->pdoStatement->execute();
            if ($method === '') {
                $result = new DataReader($this);
            } else {
                if ($fetchMode === null) {
                    $fetchMode = $this->fetchMode;
                }
                $result = call_user_func_array([$this->pdoStatement, $method], (array) $fetchMode);
                $this->pdoStatement->closeCursor();
            }
            $this->getLogger()->debug('endProfile: ' . $token, ['method' => __METHOD__]);
        } catch (\Exception $e) {
            $this->getLogger()->debug('endProfile: ' . $token, ['method' => __METHOD__]);
            throw $this->db->getSchema()->convertException($e, $sql);
        }
        if (isset($cache, $cacheKey, $info)) {
            $cache->set($cacheKey, $result, $info[1], $info[2]);
            $this->getLogger()->debug('Saved query result in cache', ['method' => __METHOD__]);
        }
        return $result;
    }

    /**
     * @return \Psr\Log\LoggerInterface;
     */
    protected function getLogger()
    {
        if ($this->_logger === null) {
            if (class_exists('\Mindy\Base\Mindy') && \Mindy\Base\Mindy::app()) {
                $this->_logger = \Mindy\Base\Mindy::app()->logger;
            } else {
                $this->_logger = new DummyObject;
            }
        }
        return $this->_logger;
    }

    public function setSql($sql)
    {
        $this->sql = $sql;
        if ($sql !== $this->sql) {
            $this->cancel();
            $this->sql = $sql;
            $this->_pendingParams = [];
            $this->params = [];
        }
        return $this;
    }

    private function getSql()
    {
        return $this->sql;
    }
}
