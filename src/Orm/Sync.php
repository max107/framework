<?php

namespace Mindy\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Fields\OneToOneField;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class Sync
 * @package Mindy\Orm
 */
class Sync
{
    /**
     * @var \Mindy\Orm\Model[]
     */
    private $_models = [];
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Sync constructor.
     * @param $models
     * @param Connection $connection
     */
    public function __construct($models, Connection $connection)
    {
        if (!is_array($models)) {
            $models = [$models];
        }
        $this->_models = $models;
        $this->connection = $connection;
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    protected function getQueryBuilder()
    {
        return QueryBuilder::getInstance($this->connection);
    }

    /**
     * @param $model ModelInterface
     * @return int
     */
    public function createTable(ModelInterface $model)
    {
        $i = 0;

        $schemaManager = $this->connection->getSchemaManager();
        $adapter = $this->getQueryBuilder()->getAdapter();
        $tableName = $adapter->getRawTableName($model->tableName());

        $columns = [];
        $indexes = [];

        foreach ($model->getMeta()->getFields() as $name => $field) {
            if (is_a($field, OneToOneField::class) && $field->reversed) {
                continue;
            }

            $field->setModel($model);

            if ($field instanceof ManyToManyField) {
                /* @var $field \Mindy\Orm\Fields\ManyToManyField */
                if ($field->through === null) {
                    $fieldTableName = $adapter->getRawTableName($field->getTableName());
                    if ($this->hasTable($fieldTableName) === false) {
                        $fieldTable = new Table($adapter->quoteTableName($fieldTableName), $field->getColumns());
                        $schemaManager->createTable($fieldTable);
                        $i += 1;
                    }
                }
            } else {
                $column = $field->getColumn();
                if (empty($column)) {
                    continue;
                }

                $columns[] = $column;
                $indexes = array_merge($indexes, $field->getSqlIndexes());
            }
        }

        if ($this->hasTable($tableName) === false) {
            $table = new Table($adapter->quoteTableName($tableName), $columns, $indexes);
            $schemaManager->createTable($table);
            $i += 1;
        }

        return $i;
    }

    /**
     * @param $model ModelInterface
     * @return int
     */
    public function dropTable(ModelInterface $model)
    {
        $i = 0;

        $adapter = $this->getQueryBuilder()->getAdapter();
//        $sql = $adapter->sqlCheckIntegrity(false);
//        $this->connection->query($sql)->execute();

        $schemaManager = $this->connection->getSchemaManager();
        foreach ($model->getMeta()->getManyToManyFields() as $field) {
            if ($field->through === null) {
                $fieldTable = $adapter->getRawTableName($field->getTableName());
                if ($this->hasTable($fieldTable)) {
                    $schemaManager->dropTable($adapter->quoteTableName($fieldTable));
                    $i += 1;
                }
            }
        }

        $modelTable = $adapter->getRawTableName($model->tableName());
        if ($this->hasTable($modelTable)) {
            $schemaManager->dropTable($adapter->quoteTableName($modelTable));
            $i += 1;
        }

//        $this->connection->query($adapter->sqlCheckIntegrity(true))->execute();

        return $i;
    }

    /**
     * @return int
     */
    public function create()
    {
        $i = 0;
        foreach ($this->_models as $model) {
            $i += $this->createTable($model);
        }
        return $i;
    }

    /**
     * Drop all tables from database
     * @return int
     */
    public function delete()
    {
        $i = 0;
        foreach ($this->_models as $model) {
            $i += $this->dropTable($model);
        }
        return $i;
    }

    /**
     * Check table in database.
     * @param null $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        if ($tableName instanceof Model) {
            $tableName = $tableName->tableName();
        }
        return $this->connection->getSchemaManager()->tablesExist([$tableName]);
    }
}
