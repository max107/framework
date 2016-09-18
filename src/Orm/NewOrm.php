<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 17:57
 */

namespace Mindy\Orm;

use Exception;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class NewOrm
 * @package Mindy\Orm
 */
class NewOrm extends NewBase
{
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

    /**
     * @param array $fields
     * @return bool
     */
    protected function updateInternal(array $fields = [])
    {
        $values = $this->getChangedAttributes($fields);
        if (empty($values)) {
            return true;
        }

        $rows = $this->objects()
            ->filter($this->getPrimaryKeyValues())
            ->update($values);

        foreach ($values as $name => $value) {
            $this->setAttribute($name, $value);
        }
        $this->attributes->resetOldAttributes();

        $this->updateRelated();

        return $rows >= 0;
    }

    protected function insertInternal(array $fields = [])
    {
        $values = $this->getChangedAttributes($fields);
        if (empty($values)) {
            return true;
        }

        $connection = static::getConnection();
        $adapter = QueryBuilder::getInstance($connection)->getAdapter();

        $tableName = $adapter->quoteTableName($adapter->getRawTableName($this->tableName()));
        $inserted = $connection->insert($tableName, $values);
        if ($inserted === false) {
            return false;
        }

        foreach (self::getMeta()->getPrimaryKeyName(true) as $primaryKeyName) {
            if (array_key_exists($primaryKeyName, $values) === false) {
                $id = $connection->lastInsertId();
                $this->setAttribute($primaryKeyName, $id);
                $values[$primaryKeyName] = $id;
            }
        }

        $this->setAttributes($values);
        $this->attributes->resetOldAttributes();

        $this->updateRelated();

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

        $this->beforeInsertInternal();

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

        $this->beforeUpdateInternal();

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
     * @param array $fields
     * @return array
     */
    public function getChangedAttributes(array $fields = []) : array
    {
        $changed = [];

        if (empty($fields)) {
            $fields = $this->getMeta()->getAttributes();
        }

        $dirty = $this->getDirtyAttributes();
        if (empty($dirty)) {
            $dirty = $fields;
        }

        foreach ($this->getPrimaryKeyValues() as $name => $value) {
            if ($value) {
                $changed[$name] = $value;
            }
        }

        $platform = $this->getConnection()->getDatabasePlatform();

        $meta = self::getMeta();
        foreach ($this->getAttributes() as $name => $attribute) {
            if (in_array($name, $fields) && in_array($name, $dirty) && $meta->hasField($name)) {
                $field = $this->getField($name);
                $sqlType = $field->getSqlType();
                if ($sqlType) {
                    if ($value = $field->convertToDatabaseValue($attribute, $platform)) {
                        $changed[$name] = $value;
                    } else {
                        $changed[$name] = $field->default;
                    }
                }
            }
        }

        return $changed;
    }
}