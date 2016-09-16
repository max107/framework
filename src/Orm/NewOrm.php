<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 17:57
 */

namespace Mindy\Orm;

use Doctrine\DBAL\Connection;
use Exception;
use function Mindy\app;
use Mindy\QueryBuilder\QueryBuilder;

class NewOrm extends NewBase
{
    /**
     * @var string
     */
    protected $using;
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @return null|\Mindy\Event\EventManager
     */
    public function getEventManager()
    {
        return null;
    }

    /**
     * Trigger event is event manager is available
     * @param $eventName
     */
    public function trigger($eventName)
    {
        $signal = $this->getEventManager();
        if ($signal) {
            $signal->send($this, $eventName);
        }
    }

    protected function updateInternal(array $fields)
    {

    }

    /**
     * @return QueryBuilder
     * @throws Exception
     */
    protected function getQueryBuilder()
    {
        return QueryBuilder::getInstance($this->getConnection());
    }

    /**
     * @return \Mindy\QueryBuilder\BaseAdapter|\Mindy\QueryBuilder\Interfaces\ISQLGenerator
     */
    protected function getAdapter()
    {
        return $this->getQueryBuilder()->getAdapter();
    }

    protected function insertInternal(array $fields)
    {
        if (empty($fields)) {
            $fields = $this->getMeta()->getAttributes();
        }

        $dirty = $this->getDirtyAttributes();
        if (empty($dirty)) {
            $dirty = $fields;
        }

        $values = [];
        foreach ($dirty as $name) {
            $field = $this->getField($name);
            $value = $field->getDbValue();
            if ($value) {
                $values[$field->getAttributeName()] = $value;
            }
        }

        debug($values, $this->attributes->getAttributes());
        if (empty($values)) {
            return true;
        }

//        if (empty($values)) {
//            foreach ($this->getPrimaryKey(true) as $key => $value) {
//                $values[$key] = $value;
//            }
//        }

        $incValues = $values;
        $primaryKeyName = self::getPrimaryKeyName();
        if (array_key_exists($primaryKeyName, $incValues) === false) {
            $incValues[$primaryKeyName] = null;
        }
        $dbValues = $this->getDbValues($incValues);

        $connection = static::getConnection();
        $adapter = QueryBuilder::getInstance($connection)->getAdapter();

        $inserted = $connection->insert($adapter->quoteTableName($adapter->getRawTableName($this->tableName())), $dbValues);
        if ($inserted === false) {
            return false;
        }

        if (array_key_exists($primaryKeyName, $values) === false) {
            $id = $connection->lastInsertId();
            $this->setAttribute($primaryKeyName, $id);
            $values[$primaryKeyName] = $id;
        }

        $this->setAttributes($values);
        $this->attributes->resetOldAttributes();

        return true;
    }

    /**
     * @param array $fields
     * @return bool
     * @throws Exception
     */
    public function insert(array $fields = []) : bool
    {
        $connection = $this->getConnection();

        $this->trigger('beforeInsert');

        $connection->beginTransaction();
        try {
            if (($inserted = $this->insertInternal($fields))) {
                $connection->commit();
                $this->setIsNewRecord(false);
            } else {
                $connection->rollBack();
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        // $this->updateRelated();

        $this->trigger('afterInsert');

        if ($inserted) {
            $this->attributes->resetOldAttributes();
        }

        return $inserted;
    }

    /**
     * @param array $fields
     * @return bool
     * @throws Exception
     */
    public function update(array $fields = []) : bool
    {
        $connection = $this->getConnection();

        $this->trigger('beforeUpdate');

        $connection->beginTransaction();
        try {
            if ($updated = $this->updateInternal($fields)) {
                $connection->commit();
            } else {
                $connection->rollBack();
            }
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        // TODO $this->updateRelated();

        $this->trigger('afterUpdate');

        if ($updated) {
            $this->attributes->resetOldAttributes();
        }
        return $updated;
    }

    /**
     * @param $connection
     * @return $this
     */
    public function using(string $connection)
    {
        $this->using($connection);
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection() : Connection
    {
        if ($this->connection === null) {
            $this->connection = app()->db->getConnection($this->using);
        }
        return $this->connection;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}