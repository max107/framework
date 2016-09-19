<?php

namespace Mindy\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use function Mindy\app;
use Mindy\Helper\Json;
use Mindy\Helper\Traits\Accessors;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\ModelInterface;

/**
 * Class Migration
 * @package Mindy\Orm
 */
class Migration
{
    use Accessors;

    /**
     * @var ModelInterface
     */
    protected $model;
    /**
     * @var string database name (key in array databases) from settings.php. Example: default
     */
    protected $connection;
    /**
     * @var string
     */
    protected $space = '        ';
    /**
     * @var array
     */
    protected $migration = [];

    /**
     * Migration constructor.
     * @param string $migrationFile
     * @param ModelInterface $model
     * @throws Exception
     */
    public function __construct(string $migrationFile, ModelInterface $model)
    {
        if (!is_file($migrationFile)) {
            throw new Exception('File not found');
        }
        $this->migration = json_decode(file_get_contents($migrationFile));

        $this->model = $model;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param ModelInterface $model
     * @return array
     */
    public function getFields(ModelInterface $model) : array
    {
        $fields = [];
        $modelFields = $model->getMeta()->getFields();
        foreach ($modelFields as $name => $field) {
            if ($field->getSqlType()) {
                $options = $field->getSqlOptions();

                $fields[$field->getAttributeName()] = array_merge($options, [
                    'hash' => md5(serialize($options))
                ]);;
            }
        }
        return $fields;
    }

    /**
     * @return string json encoded field options
     */
    public function exportFields()
    {
        return json_encode($this->getFields(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    public function hasChanges($migrationFile, ModelInterface $model)
    {
        $migrations = [];
        list($name, $timestamp) = explode('_', basename($migrationFile));
        if (!isset($migrations[$name])) {
            $migrations[$name] = [];
        }
        $migrations[$name][] = $timestamp;

        $currentFields = $this->getFields($model);
        if (count($this->migration) != count($currentFields)) {
            return true;
        }

        foreach ($this->migration as $name => $field) {
            if (array_key_exists($name, $currentFields)) {
                if ($field['hash'] != $currentFields[$name]['hash']) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    public function getSafeUp(ModelInterface $model)
    {
        $lines = [];
        $added = [];
        $deleted = [];
        $fields = $this->getFields($model);

        foreach ($fields as $name => $field) {
            if (array_key_exists($name, $this->migration) === false) {
                $added[$name] = $field;
            }
        }

        if (!empty($lastMigrationFields)) {
            foreach ($lastMigrationFields as $name => $field) {
                if (array_key_exists($name, $fields) === false) {
                    $deleted[$name] = $field;
                    continue;
                }

                if ($field['hash'] == $fields[$name]['hash']) {
                    continue;
                }

                if ($field['sqlType'] != $fields[$name]['sqlType']) {
                    $lines[] = $this->space . '$this->alterColumn("' . $this->model->tableName() . '", "' . $name . '", "' . $fields[$name]['sqlType'] . '");';
                } elseif ($field['sqlType'] == $fields[$name]['sqlType'] && $fields['length'] != $fields[$name]['length']) {
                    $lines[] = $this->space . '$this->alterColumn("' . $this->model->tableName() . '", "' . $name . '", "' . $fields[$name]['sqlType'] . '");';
                }
            }

            foreach ($deleted as $name => $field) {
                $lines[] = $this->space . '$this->dropColumn("' . $this->model->tableName() . '", "' . $name . '");';
            }
        }

        $schema = app()->db->getConnection()->getSchema();
        if (empty($lastMigrationFields)) {
            $columns = [];
            foreach ($this->model->getMeta()->getFields() as $name => $field) {
                $field->setModel($this->model);

                if ($field->getSqlType() !== false) {
                    $columns[$field->getAttributeName()] = $field->getSql($schema);
                } else if ($field instanceof ManyToManyField) {
                    /* @var $field \Mindy\Orm\Fields\ManyToManyField */
                    if ($field->through === null) {
                        $lines[] = $this->space . 'if ($this->hasTable("' . $field->getTableName() . '") === false) {';
                        $lines[] = $this->space . $this->space . '$this->createTable("' . $field->getTableName() . '", ' . $this->compileColumns($field->getColumns($schema)) . ', null, true);';
                        $lines[] = $this->space . '}';
                    }
                }
            }

            $lines[] = $this->space . '$this->createTable("' . $this->model->tableName() . '", ' . $this->compileColumns($columns) . ');';
        } else {
            foreach ($added as $name => $field) {
                $lines[] = $this->space . '$this->addColumn("' . $this->model->tableName() . '", "' . $name . '", "' . $field['sqlType'] . '");';
            }
        }

        return implode("\n", $lines);
    }

    protected function compileColumns(array $columns)
    {
        $codeColumns = "[\n";
        foreach ($columns as $name => $sql) {
            $codeColumns .= ($this->space . '    ') . '"' . $name . '" => "' . $sql . '",';
            $codeColumns .= "\n";
        }
        $codeColumns .= $this->space . "]";
        return $codeColumns;
    }

    public function getSafeDown()
    {
        if (count($this->getMigrations()) == 0) {
            return $this->space . '$this->dropTable("' . $this->model->tableName() . '");';
        } else {
            $lines = [];
            $deleted = [];
            $fields = $this->getFields();
            $lastMigrationFields = $this->getLastMigration();

            foreach ($lastMigrationFields as $name => $field) {
                if (array_key_exists($name, $fields) === false) {
                    $added[$name] = $field;
                }
            }

            foreach ($fields as $name => $field) {
                if (array_key_exists($name, $lastMigrationFields) === false) {
                    $deleted[$name] = $field;
                    continue;
                }

                if ($field['hash'] == $lastMigrationFields[$name]['hash']) {
                    continue;
                }

                if ($field['sqlType'] != $lastMigrationFields[$name]['sqlType']) {
                    $lines[] = $this->space . '$this->alterColumn("' . $this->model->tableName() . '", "' . $name . '", "' . $lastMigrationFields[$name]['sqlType'] . '");';
                } elseif ($field['sqlType'] == $lastMigrationFields[$name]['sqlType'] && $fields['length'] != $lastMigrationFields[$name]['length']) {
                    $lines[] = $this->space . '$this->alterColumn("' . $this->model->tableName() . '", "' . $name . '", "' . $lastMigrationFields[$name]['sqlType'] . '");';
                }
            }

            foreach ($deleted as $name => $field) {
                $lines[] = $this->space . '$this->dropColumn("' . $this->model->tableName() . '", "' . $name . '");';
            }
            return implode("\n", $lines);
        }
    }
}
