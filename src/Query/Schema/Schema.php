<?php

namespace Mindy\Query\Schema;

use Exception;
use Mindy\Cache\Cache;
use Mindy\Cache\TagDependency;
use Mindy\Exception\InvalidCallException;

/**
 * Schema is the base class for concrete DBMS-specific schema classes.
 *
 * Schema represents the database schema information that is DBMS specific.
 *
 * @property string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the
 * sequence object. This property is read-only.
 * @property \Mindy\QueryBuilder\QueryBuilder $queryBuilder The query builder for this connection. This property is read-only.
 * @property string[] $tableNames All table names in the database. This property is read-only.
 * @property TableSchema[] $tableSchemas The metadata for all tables in the database. Each array element is an
 * instance of [[TableSchema]] or its child class. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 * @package Mindy\Query
 */
abstract class Schema
{
    /**
     * The followings are the supported abstract column data types.
     */
    const TYPE_PK = 'pk';
    const TYPE_BIGPK = 'bigpk';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIGINT = 'bigint';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';
    const TYPE_BINARY = 'binary';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_MONEY = 'money';
    /**
     * @var \Mindy\Query\Connection the database connection
     */
    public $db;
    /**
     * @var string the default schema name used for the current session.
     */
    public $defaultSchema;
    /**
     * @var
     */
    public $adapter;
    /**
     * @var array map of DB errors and corresponding exceptions
     * If left part is found in DB error message exception class from the right part is used.
     */
    public $exceptionMap = [
        'SQLSTATE[23' => 'Mindy\Query\Exception\Exception',
    ];
    /**
     * @var array
     */
    public $typeMap = [];
    /**
     * @var array
     */
    public $phpTypeMap = [];
    /**
     * @var array list of ALL table names in the database
     */
    private $_tableNames = [];
    /**
     * @var array list of loaded table metadata (table name => TableSchema)
     */
    private $_tables = [];

    /**
     * @return \Mindy\Query\Schema\ColumnSchema
     * @throws \Exception
     */
    protected function createColumnSchema()
    {
        return new ColumnSchema;
    }

    /**
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return TableSchema DBMS-dependent table metadata, null if the table does not exist.
     */
    abstract protected function loadTableSchema($name);

    /**
     * Returns the actual name of a given table name.
     * This method will strip off curly brackets from the given table name
     * and replace the percentage character '%' with [[Connection::tablePrefix]].
     * @param string $name the table name to be converted
     * @return string the real name of the given table name
     */
    public function getRawTableName($name)
    {
        if (strpos($name, '{{') !== false) {
            $name = preg_replace('/\\{\\{(.*?)\\}\\}/', '\1', $name);
            return str_replace('%', $this->getDb()->getTablePrefix(), $name);
        } else {
            return $name;
        }
    }

    /**
     * Obtains the metadata for the named table.
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param boolean $refresh whether to reload the table schema even if it is found in the cache.
     * @return TableSchema table metadata. Null if the named table does not exist.
     */
    public function getTableSchema($name, $refresh = false)
    {
        if (array_key_exists($name, $this->_tables) && !$refresh) {
            return $this->_tables[$name];
        }
        $connection = $this->getDb();
        $realName = $this->getRawTableName($name);
        if ($connection->enableSchemaCache && !in_array($name, $connection->schemaCacheExclude, true)) {
            /* @var $cache Cache */
            if (is_string($connection->schemaCache) && class_exists('\Mindy\Base\Mindy') && \Mindy\Base\Mindy::app()) {
                $cache = \Mindy\Base\Mindy::app()->getComponent($connection->schemaCache, false);
            } else {
                $cache = $connection->schemaCache;
            }
            if ($cache instanceof Cache) {
                $key = $this->getCacheKey($name);
                if ($refresh || ($table = $cache->get($key)) === false) {
                    $this->_tables[$name] = $table = $this->loadTableSchema($realName);
                    if ($table !== null) {
                        $cache->set($key, $table, $connection->schemaCacheDuration, new TagDependency([
                            'tags' => $this->getCacheTag(),
                        ]));
                    }
                } else {
                    $this->_tables[$name] = $table;
                }
                return $this->_tables[$name];
            }
        }
        return $this->_tables[$name] = $this->loadTableSchema($realName);
    }

    /**
     * Returns the cache key for the specified table name.
     * @param string $name the table name
     * @return mixed the cache key
     */
    protected function getCacheKey($name)
    {
        return [
            __CLASS__,
            $this->db->dsn,
            $this->db->username,
            $name,
        ];
    }

    /**
     * Returns the cache tag name.
     * This allows [[refresh()]] to invalidate all cached table schemas.
     * @return string the cache tag name
     */
    protected function getCacheTag()
    {
        return md5(serialize([
            __CLASS__,
            $this->db->dsn,
            $this->db->username,
        ]));
    }

    /**
     * Returns the metadata for all tables in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * @param boolean $refresh whether to fetch the latest available table schemas. If this is false,
     * cached data may be returned if available.
     * @return TableSchema[] the metadata for all tables in the database.
     * Each array element is an instance of [[TableSchema]] or its child class.
     */
    public function getTableSchemas($schema = '', $refresh = false)
    {
        $tables = [];
        foreach ($this->getTableNames($schema, $refresh) as $name) {
            if ($schema !== '') {
                $name = $schema . '.' . $name;
            }
            if (($table = $this->getTableSchema($name, $refresh)) !== null) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema name.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @param boolean $refresh whether to fetch the latest available table names. If this is false,
     * table names fetched previously (if available) will be returned.
     * @return string[] all table names in the database.
     */
    public function getTableNames($schema = '', $refresh = false)
    {
        if (!isset($this->_tableNames[$schema]) || $refresh) {
            $this->_tableNames[$schema] = $this->findTableNames($schema);
        }
        return $this->_tableNames[$schema];
    }

    /**
     * Determines the PDO type for the given PHP data value.
     * @param mixed $data the data whose PDO type is to be determined
     * @return integer the PDO type
     * @see http://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType($data)
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => \PDO::PARAM_BOOL,
            'integer' => \PDO::PARAM_INT,
            'string' => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL' => \PDO::PARAM_NULL,
        ];
        $type = gettype($data);
        return isset($typeMap[$type]) ? $typeMap[$type] : \PDO::PARAM_STR;
    }

    /**
     * Refreshes the schema.
     * This method cleans up all cached table schemas so that they can be re-created later
     * to reflect the database schema change.
     */
    public function refresh()
    {
        /* @var $cache Cache */
        if (is_string($this->db->schemaCache) && class_exists('\Mindy\Base\Mindy') && \Mindy\Base\Mindy::app()) {
            $cache = \Mindy\Base\Mindy::app()->getComponent($this->db->schemaCache, false);
        } else {
            $cache = $this->db->schemaCache;
        }
        if ($this->db->enableSchemaCache && $cache instanceof Cache) {
            TagDependency::invalidate($cache, $this->getCacheTag());
        }
        $this->_tableNames = [];
        $this->_tables = [];
    }

    /**
     * Returns all table names in the database.
     * This method should be overridden by child classes in order to support this feature
     * because the default implementation simply throws an exception.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @return array all table names in the database. The names have NO schema name prefix.
     * @throws Exception if this method is called
     */
    protected function findTableNames($schema = '')
    {
        throw new Exception(get_class($this) . ' does not support fetching all table names.');
    }

    /**
     * Returns all unique indexes for the given table.
     * Each array element is of the following structure:
     *
     * ~~~
     * [
     *  'IndexName1' => ['col1' [, ...]],
     *  'IndexName2' => ['col2' [, ...]],
     * ]
     * ~~~
     *
     * This method should be overridden by child classes in order to support this feature
     * because the default implementation simply throws an exception
     * @param TableSchema $table the table metadata
     * @return array all unique indexes for the given table.
     * @throws Exception if this method is called
     */
    public function findUniqueIndexes($table)
    {
        throw new Exception(get_class($this) . ' does not support getting unique indexes information.');
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @throws InvalidCallException if the DB connection is not active
     * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID($sequenceName = '')
    {
        if ($this->db->isActive) {
            return $this->db->pdo->lastInsertId($sequenceName === '' ? null : $sequenceName);
        } else {
            throw new Exception('DB Connection is not active.');
        }
    }

    /**
     * @return boolean whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint()
    {
        return $this->db->enableSavepoint;
    }

    /**
     * Creates a new savepoint.
     * @param string $name the savepoint name
     */
    public function createSavepoint($name)
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    /**
     * Releases an existing savepoint.
     * @param string $name the savepoint name
     */
    public function releaseSavepoint($name)
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }

    /**
     * Rolls back to a previously created savepoint.
     * @param string $name the savepoint name
     */
    public function rollBackSavepoint($name)
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    /**
     * Sets the isolation level of the current transaction.
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of [[Transaction::READ_UNCOMMITTED]], [[Transaction::READ_COMMITTED]], [[Transaction::REPEATABLE_READ]]
     * and [[Transaction::SERIALIZABLE]] but also a string containing DBMS specific syntax to be used
     * after `SET TRANSACTION ISOLATION LEVEL`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setTransactionIsolationLevel($level)
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level;")->execute();
    }

    /**
     * Extracts the PHP type from abstract DB type.
     * @param ColumnSchema $column the column schema information
     * @return string PHP type name
     */
    protected function getColumnPhpType($column)
    {
        static $typeMap = [
            // abstract type => php type
            'smallint' => 'integer',
            'integer' => 'integer',
            'bigint' => 'integer',
            'boolean' => 'boolean',
            'float' => 'double',
            'binary' => 'resource',
        ];
        if (isset($typeMap[$column->type])) {
            if ($column->type === 'bigint') {
                return PHP_INT_SIZE == 8 && !$column->unsigned ? 'integer' : 'string';
            } elseif ($column->type === 'integer') {
                return PHP_INT_SIZE == 4 && $column->unsigned ? 'string' : 'integer';
            } else {
                return $typeMap[$column->type];
            }
        } else {
            return 'string';
        }
    }

    /**
     * Converts a DB exception to a more concrete one if possible.
     *
     * @param \Exception $e
     * @param string $rawSql SQL that produced exception
     * @return Exception
     */
    public function convertException(\Exception $e, $rawSql)
    {
        if ($e instanceof \Mindy\Query\Exception\Exception) {
            return $e;
        }

        $message = $e->getMessage() . "\nThe SQL being executed was: $rawSql";
        $errorInfo = $e instanceof \PDOException ? $e->errorInfo : null;

        $exceptionClass = \Mindy\Query\Exception\Exception::class;
        foreach ($this->exceptionMap as $error => $class) {
            if (strpos($e->getMessage(), $error) !== false) {
                $exceptionClass = $class;
            }
        }
        return new $exceptionClass($message, $errorInfo, (int)$e->getCode(), $e);
    }

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     * @param string $sql the SQL statement
     * @return boolean whether a SQL statement is for read purpose.
     */
    public function isReadQuery($sql)
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';
        return preg_match($pattern, $sql) > 0;
    }

    /**
     * @return \Mindy\QueryBuilder\BaseAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * @return \Mindy\Query\Connection
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Converts an abstract column type into a physical column type.
     * The conversion is done using the type map specified in [[typeMap]].
     * The following abstract column types are supported (using MySQL as an example to explain the corresponding
     * physical types):
     *
     * - `pk`: an auto-incremental primary key type, will be converted into "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"
     * - `bigpk`: an auto-incremental primary key type, will be converted into "bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY"
     * - `string`: string type, will be converted into "varchar(255)"
     * - `text`: a long string type, will be converted into "text"
     * - `smallint`: a small integer type, will be converted into "smallint(6)"
     * - `integer`: integer type, will be converted into "int(11)"
     * - `bigint`: a big integer type, will be converted into "bigint(20)"
     * - `boolean`: boolean type, will be converted into "tinyint(1)"
     * - `float``: float number type, will be converted into "float"
     * - `decimal`: decimal number type, will be converted into "decimal"
     * - `datetime`: datetime type, will be converted into "datetime"
     * - `timestamp`: timestamp type, will be converted into "timestamp"
     * - `time`: time type, will be converted into "time"
     * - `date`: date type, will be converted into "date"
     * - `money`: money type, will be converted into "decimal(19,4)"
     * - `binary`: binary data type, will be converted into "blob"
     *
     * If the abstract type contains two or more parts separated by spaces (e.g. "string NOT NULL"), then only
     * the first part will be converted, and the rest of the parts will be appended to the converted result.
     * For example, 'string NOT NULL' is converted to 'varchar(255) NOT NULL'.
     *
     * For some of the abstract types you can also specify a length or precision constraint
     * by appending it in round brackets directly to the type.
     * For example `string(32)` will be converted into "varchar(32)" on a MySQL database.
     * If the underlying DBMS does not support these kind of constraints for a type it will
     * be ignored.
     *
     * If a type cannot be found in [[typeMap]], it will be returned without any change.
     * @param string $type abstract column type
     * @return string physical column type.
     */
    public function getColumnType($type)
    {
        if (isset($this->phpTypeMap[$type])) {
            return $this->phpTypeMap[$type];
        } elseif (preg_match('/^(\w+)\((.+?)\)(.*)$/', $type, $matches)) {
            if (isset($this->phpTypeMap[$matches[1]])) {
                return preg_replace('/\(.+\)/', '(' . $matches[2] . ')', $this->phpTypeMap[$matches[1]]) . $matches[3];
            }
        } elseif (preg_match('/^(\w+)\s+/', $type, $matches)) {
            if (isset($this->phpTypeMap[$matches[1]])) {
                return preg_replace('/^\w+/', $this->phpTypeMap[$matches[1]], $type);
            }
        }
        return $type;
    }
}
